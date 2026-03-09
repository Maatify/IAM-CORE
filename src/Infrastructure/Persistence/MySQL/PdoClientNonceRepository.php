<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 04:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use DateTimeImmutable;
use PDOException;
use Maatify\Iam\Domain\Repository\ClientNonceRepositoryInterface;

final class PdoClientNonceRepository extends AbstractPdoRepository implements ClientNonceRepositoryInterface
{
    public function storeIfUnused(int $clientId, string $nonce, DateTimeImmutable $expiresAt): bool
    {
        try {

            $stmt = $this->pdo->prepare(
                '
            INSERT INTO iam_client_request_nonces (client_id, nonce, expires_at)
            VALUES (:client_id, :nonce, :expires_at)
            '
            );

            $stmt->execute([
                'client_id'  => $clientId,
                'nonce'      => $nonce,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);

            return true;

        } catch (PDOException) {
            return false;
        }
    }
}
