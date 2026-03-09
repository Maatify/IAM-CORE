<?php

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\IdempotencyRepositoryInterface;
use PDOException;

final class PdoIdempotencyRepository extends AbstractPdoRepository implements IdempotencyRepositoryInterface
{
    private const PROCESSING_TTL_SECONDS = 30;

    /**
     * @return array{
     *     request_hash:string,
     *     status:string,
     *     response_body:string|null,
     *     status_code:int|null
     * }|null
     */
    public function find(int $clientId, string $key): ?array
    {
        $row = $this->fetchOne(
            '
            SELECT
                request_hash,
                status,
                response_body,
                status_code
            FROM iam_idempotency_keys
            WHERE client_id = :client
            AND idempotency_key = :key
            LIMIT 1
            ',
            [
                'client' => $clientId,
                'key'    => $key,
            ]
        );

        if (!$row) {
            return null;
        }

        return [
            'request_hash'  => DbRow::string($row, 'request_hash'),
            'status'        => DbRow::string($row, 'status'),
            'response_body' => DbRow::nullableString($row, 'response_body'),
            'status_code'   => array_key_exists('status_code', $row) && $row['status_code'] !== null
                ? DbRow::int($row, 'status_code')
                : null,
        ];
    }

    public function claimProcessing(
        int $clientId,
        string $key,
        string $requestHash
    ): bool {

        try {

            $stmt = $this->pdo->prepare(
                '
                INSERT INTO iam_idempotency_keys
                (
                    client_id,
                    idempotency_key,
                    request_hash,
                    status,
                    response_body,
                    status_code,
                    processing_expires_at
                )
                VALUES
                (
                    :client,
                    :key,
                    :hash,
                    :status,
                    NULL,
                    NULL,
                    DATE_ADD(NOW(), INTERVAL :ttl SECOND)
                )
                '
            );

            $stmt->execute([
                'client' => $clientId,
                'key'    => $key,
                'hash'   => $requestHash,
                'status' => 'PROCESSING',
                'ttl'    => self::PROCESSING_TTL_SECONDS
            ]);

            return true;

        } catch (PDOException) {

            // Attempt reclaim if existing PROCESSING lock expired

            $stmt = $this->pdo->prepare(
                '
                UPDATE iam_idempotency_keys
                SET
                    request_hash = :hash,
                    processing_expires_at = DATE_ADD(NOW(), INTERVAL :ttl SECOND)
                WHERE
                    client_id = :client
                AND idempotency_key = :key
                AND status = "PROCESSING"
                AND processing_expires_at < NOW()
                '
            );

            $stmt->execute([
                'client' => $clientId,
                'key'    => $key,
                'hash'   => $requestHash,
                'ttl'    => self::PROCESSING_TTL_SECONDS
            ]);

            return $stmt->rowCount() === 1;
        }
    }

    public function markDone(
        int $clientId,
        string $key,
        string $requestHash,
        int $status,
        string $responseBody
    ): void {

        $stmt = $this->pdo->prepare(
            '
            UPDATE iam_idempotency_keys
            SET
                status = :row_status,
                status_code = :status_code,
                response_body = :response_body,
                processing_expires_at = NULL
            WHERE client_id = :client
            AND idempotency_key = :key
            AND request_hash = :hash
            '
        );

        $stmt->execute([
            'row_status'    => 'DONE',
            'status_code'   => $status,
            'response_body' => $responseBody,
            'client'        => $clientId,
            'key'           => $key,
            'hash'          => $requestHash,
        ]);
    }
}
