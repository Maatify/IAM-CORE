<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Routes;

use Maatify\Iam\Presentation\Http\Controllers\HealthController;
use Maatify\Iam\Presentation\Http\Controllers\VersionController;
use Maatify\Iam\Presentation\Http\Middleware\ClientAuthMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\IdempotencyMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\TrustedNetworkMiddleware;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

final class RoutesProvider
{
    /**
     * @param App<ContainerInterface|null> $app
     */
    public static function register(App $app): void
    {
        $app->get('/health', HealthController::class);
        $app->get('/version', VersionController::class);

        // Internal APIs (network-trusted only)
        $app->group('/internal', function (RouteCollectorProxy $group): void {
            $group->post(
                '/actors',
                \Maatify\Iam\Presentation\Http\Controllers\ProvisionActorController::class
            );
        })
            ->add(IdempotencyMiddleware::class)
            ->add(ClientAuthMiddleware::class)
            ->add(TrustedNetworkMiddleware::class);
    }
}
