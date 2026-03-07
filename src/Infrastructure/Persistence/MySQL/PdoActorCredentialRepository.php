<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 01:36
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\ActorCredentialRepositoryInterface;
use PDO;

final readonly class PdoActorCredentialRepository implements ActorCredentialRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function createPasswordCredential(
        int $actorId,
        string $hash,
        string $pepperId
    ): void {

        $stmt = $this->pdo->prepare(
            'INSERT INTO iam_actor_credentials
            (actor_id, credential_type, secret_hash, pepper_id)
            VALUES (:actor_id, "PASSWORD", :hash, :pepper_id)'
        );

        $stmt->execute([
            'actor_id' => $actorId,
            'hash' => $hash,
            'pepper_id' => $pepperId
        ]);
    }
}
