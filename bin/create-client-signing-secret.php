<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 04:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

use Dotenv\Dotenv;
use Maatify\Iam\Application\Security\ClientSigningSecretProvisionService;
use Maatify\Iam\Bootstrap\ContainerFactory;
use Maatify\Iam\Bootstrap\Settings;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->load();

if ($argc < 2) {
    fwrite(STDERR, "Usage: php bin/create-client-signing-secret.php <client_id>\n");
    exit(1);
}

$clientId = (int) $argv[1];

$settings = Settings::fromEnv();
$container = ContainerFactory::build($settings);

/** @var ClientSigningSecretProvisionService $service */
$service = $container->get(ClientSigningSecretProvisionService::class);

$secret = $service->createForClient($clientId);

echo "RAW SIGNING SECRET:\n";
echo $secret . PHP_EOL;
