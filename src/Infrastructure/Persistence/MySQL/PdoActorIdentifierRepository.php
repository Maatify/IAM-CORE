<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-04 03:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\ActorIdentifierRepositoryInterface;
use PDO;

final readonly class PdoActorIdentifierRepository implements ActorIdentifierRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function existsByLookup(
        int $tenantId,
        string $actorType,
        string $identifierType,
        string $lookupHashBinary32
    ): bool {
        $sql = 'SELECT 1
                FROM iam_actor_identifiers
                WHERE tenant_id = :tenant_id
                  AND actor_type = :actor_type
                  AND identifier_type = :identifier_type
                  AND lookup_hash = :lookup_hash
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('actor_type', $actorType, PDO::PARAM_STR);
        $stmt->bindValue('identifier_type', $identifierType, PDO::PARAM_STR);
        $stmt->bindValue('lookup_hash', $lookupHashBinary32, PDO::PARAM_LOB);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function insert(
        int $actorId,
        int $tenantId,
        string $actorType,
        string $identifierType,
        string $lookupHashBinary32,
        string $cipher,
        string $iv,
        string $tag,
        string $keyId,
        string $algorithm,
        bool $isVerified = false
    ): void {
        $sql = 'INSERT INTO iam_actor_identifiers (
                    actor_id, tenant_id, actor_type,
                    identifier_type, lookup_hash,
                    cipher, iv, tag, key_id, algorithm,
                    is_verified
                ) VALUES (
                    :actor_id, :tenant_id, :actor_type,
                    :identifier_type, :lookup_hash,
                    :cipher, :iv, :tag, :key_id, :algorithm,
                    :is_verified
                )';

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue('actor_id', $actorId, PDO::PARAM_INT);
        $stmt->bindValue('tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('actor_type', $actorType, PDO::PARAM_STR);

        $stmt->bindValue('identifier_type', $identifierType, PDO::PARAM_STR);
        $stmt->bindValue('lookup_hash', $lookupHashBinary32, PDO::PARAM_LOB);

        $stmt->bindValue('cipher', $cipher, PDO::PARAM_LOB);
        $stmt->bindValue('iv', $iv, PDO::PARAM_LOB);
        $stmt->bindValue('tag', $tag, PDO::PARAM_LOB);
        $stmt->bindValue('key_id', $keyId, PDO::PARAM_STR);
        $stmt->bindValue('algorithm', $algorithm, PDO::PARAM_STR);

        $stmt->bindValue('is_verified', $isVerified ? 1 : 0, PDO::PARAM_INT);

        $stmt->execute();
    }
}
