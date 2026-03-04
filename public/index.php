<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

use Dotenv\Dotenv;
use Maatify\Iam\Bootstrap\AppFactory;
use Maatify\Iam\Bootstrap\ContainerFactory;
use Maatify\Iam\Bootstrap\Settings;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->load();

$settings = Settings::fromEnv();

$container = ContainerFactory::build($settings);

$app = AppFactory::build($container);

$app->run();
