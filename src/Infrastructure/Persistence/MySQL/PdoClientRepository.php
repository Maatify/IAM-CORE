<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 00:04
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use Maatify\Iam\Domain\Entity\Client;
use Maatify\Iam\Domain\Repository\ClientRepositoryInterface;

final class PdoClientRepository extends AbstractPdoRepository implements ClientRepositoryInterface
{
    public function findByClientKey(string $clientKey): ?Client
    {
        $row = $this->fetchOne(
            '
            SELECT
                id,
                tenant_id,
                client_key,
                type,
                status
            FROM iam_clients
            WHERE client_key = :key
            LIMIT 1
            ',
            ['key' => $clientKey]
        );

        if (!$row) {
            return null;
        }

        return new Client(
            DbRow::int($row, 'id'),
            DbRow::int($row, 'tenant_id'),
            DbRow::string($row, 'client_key'),
            DbRow::string($row, 'type'),
            DbRow::string($row, 'status')
        );
    }

    public function findById(int $id): ?Client
    {
        $row = $this->fetchOne(
            '
        SELECT
            id,
            tenant_id,
            client_key,
            type,
            status
        FROM iam_clients
        WHERE id = :id
        LIMIT 1
        ',
            ['id' => $id]
        );

        if (!$row) {
            return null;
        }

        return new Client(
            DbRow::int($row, 'id'),
            DbRow::int($row, 'tenant_id'),
            DbRow::string($row, 'client_key'),
            DbRow::string($row, 'type'),
            DbRow::string($row, 'status')
        );
    }
}
