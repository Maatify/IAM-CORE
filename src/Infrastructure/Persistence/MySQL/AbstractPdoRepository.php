<?php

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use PDO;

abstract class AbstractPdoRepository
{
    public function __construct(
        protected PDO $pdo
    ) {
    }

    /**
     * Fetch a single row or null.
     *
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>|null
     */
    protected function fetchOne(string $sql, array $params): ?array
    {
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @param array<string, scalar|null> $params
     * @return array<int, array<string,mixed>>
     */
    protected function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        /** @var array<int, array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }
}
