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
use Tests\Support\TestDatabaseManager;

$rootPath = dirname(__DIR__);

// ------------------------------------------------------------
// 1) Load .env.test (preferred) then fallback to .env
// ------------------------------------------------------------
if (file_exists($rootPath . '/.env.test')) {
    Dotenv::createImmutable($rootPath, '.env.test')->safeLoad();
}

// ------------------------------------------------------------
// 2) Minimal defaults for CI safety (do NOT rely on local secrets)
// ------------------------------------------------------------
$defaults = [
    'APP_ENV'           => 'testing',
    'DB_HOST'           => '127.0.0.1',
    'DB_NAME'           => 'iam_core_test',
    'DB_USER'           => 'root',
    'DB_PASS'           => 'root',

    // Blind-index / lookup HMAC secret
    'EMAIL_BLIND_INDEX_KEY' => 'test-blind-index-key-32-chars-long-!!',

    // Crypto keys (must be 32 bytes for AES-256-GCM)
    'CRYPTO_ACTIVE_KEY_ID' => 'v1',
    'CRYPTO_KEYS' => '[{"id":"v1","key":"12345678901234567890123456789012"}]',
];

// ------------------------------------------------------------
// 3) Sync $_ENV into putenv() (important for PDO / legacy libs)
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// 4) Create tables once per test run
// ------------------------------------------------------------
TestDatabaseManager::migrate();
