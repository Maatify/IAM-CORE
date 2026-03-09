<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 00:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Repository\ClientSecretRepositoryInterface;

final class PdoClientSecretRepository extends AbstractPdoRepository implements ClientSecretRepositoryInterface
{
    /**
     * @return string[]
     */
    public function findHashesByClientId(int $clientId): array
    {
        $rows = $this->fetchAll(
            '
            SELECT secret_hash
            FROM iam_client_secrets
            WHERE client_id = :client
            ',
            ['client' => $clientId]
        );

        /** @var array<int, array{secret_hash:string}> $rows */
        return array_column($rows, 'secret_hash');
    }
}
