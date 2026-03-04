<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-04 02:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;

final class TestDatabaseManager
{
    private static ?PDO $pdo = null;
    private static bool $migrated = false;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = (string)($_ENV['DB_HOST'] ?? '');
        $port = (string)($_ENV['DB_PORT'] ?? '3306');
        $name = (string)($_ENV['DB_NAME'] ?? '');
        $user = (string)($_ENV['DB_USER'] ?? '');
        $pass = (string)($_ENV['DB_PASS'] ?? '');

        if ($host === '' || $name === '' || $user === '') {
            throw new RuntimeException('Test DB env vars missing (DB_HOST/DB_NAME/DB_USER).');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        self::$pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$pdo;
    }

    public static function migrate(): void
    {
        if (self::$migrated === true) {
            return;
        }

        $pdo = self::connection();

        $schemaPath = dirname(__DIR__, 2) . '/database/schema/iam_v1_schema_mysql.sql';

        if (!file_exists($schemaPath)) {
            throw new RuntimeException('IAM schema file not found: ' . $schemaPath);
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException('Unable to read IAM schema file.');
        }

        $pdo->exec($sql);

        self::$migrated = true;
    }

    public static function truncateAll(): void
    {
        // Ensure schema exists
        self::migrate();

        $pdo = self::connection();

        // Leaf → Root (respect FK)
        $tables = [
            'iam_sessions',
            'iam_actor_credentials',
            'iam_actor_identifiers',
            'iam_actors',
            'iam_clients',
            'iam_tenants',
        ];

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $pdo->exec('TRUNCATE TABLE `' . $table . '`');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
