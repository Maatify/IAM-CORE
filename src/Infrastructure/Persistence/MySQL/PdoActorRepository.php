<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-04 03:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\ActorRepositoryInterface;
use PDO;

final readonly class PdoActorRepository implements ActorRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function insert(
        int $tenantId,
        string $actorType,
        string $status,
    ): int {
        $sql = 'INSERT INTO iam_actors (tenant_id, actor_type, status) VALUES (:tenant_id, :actor_type, :status)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tenant_id'  => $tenantId,
            'actor_type' => $actorType,
            'status'     => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
