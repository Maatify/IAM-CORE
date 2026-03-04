<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-04 02:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

// Load .env.test if exists
if (file_exists($rootPath . '/.env.test')) {
    Dotenv::createImmutable($rootPath, '.env.test')->safeLoad();
}

// Minimal deterministic defaults
$defaults = [
    'APP_ENV'           => 'testing',
    'DB_HOST'           => '127.0.0.1',
    'DB_NAME'           => 'iam_core_test',
    'DB_USER'           => 'root',
    'DB_PASS'           => '',
    'IAM_LOOKUP_SECRET' => 'test-lookup-secret-32-chars-long!!!!',
];

foreach ($defaults as $key => $value) {
    if (! isset($_ENV[$key])) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Sync env for PDO
foreach ($_ENV as $key => $value) {
    if (is_scalar($value)) {
        putenv($key . '=' . (string)$value);
    }
}