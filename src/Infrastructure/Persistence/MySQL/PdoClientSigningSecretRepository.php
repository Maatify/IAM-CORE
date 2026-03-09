<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\ClientSigningSecretRepositoryInterface;

final class PdoClientSigningSecretRepository extends AbstractPdoRepository implements ClientSigningSecretRepositoryInterface
{
    public function findActiveByClientId(int $clientId): array
    {
        $rows = $this->fetchAll(
            "
            SELECT
                cipher,
                iv,
                tag,
                key_id,
                algorithm
            FROM iam_client_signing_secrets
            WHERE client_id = :client
            AND status = 'ACTIVE'
            AND (expires_at IS NULL OR expires_at > NOW())
            ",
            [
                'client' => $clientId
            ]
        );

        /** @var list<array{
         *     cipher:string,
         *     iv:string,
         *     tag:string,
         *     key_id:string,
         *     algorithm:string
         * }> $rows */

        return $rows;
    }

    public function storeSecret(
        int $clientId,
        string $cipher,
        string $iv,
        string $tag,
        string $keyId,
        string $algorithm
    ): void {

        $stmt = $this->pdo->prepare('
        INSERT INTO iam_client_signing_secrets
        (client_id, cipher, iv, tag, key_id, algorithm)
        VALUES (:client, :cipher, :iv, :tag, :key_id, :algorithm)
    ');

        $stmt->bindValue(':client', $clientId, \PDO::PARAM_INT);
        $stmt->bindValue(':cipher', $cipher, \PDO::PARAM_LOB);
        $stmt->bindValue(':iv', $iv, \PDO::PARAM_LOB);
        $stmt->bindValue(':tag', $tag, \PDO::PARAM_LOB);
        $stmt->bindValue(':key_id', $keyId);
        $stmt->bindValue(':algorithm', $algorithm);

        $stmt->execute();
    }
}
