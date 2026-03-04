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

namespace Maatify\Iam\Bootstrap;

use DI\Container;
use Maatify\Iam\Presentation\Http\Middleware\IamExceptionMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\RequestIdMiddleware;
use Maatify\Iam\Presentation\Http\Routes\RoutesProvider;
use Middlewares\TrailingSlash;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory as SlimAppFactory;
use Slim\App;

final class AppFactory
{
    /**
     * @return App<ContainerInterface|null>
     */
    public static function build(Container $container): App
    {
        SlimAppFactory::setContainer($container);

        $app = SlimAppFactory::create();

        // 1) Routing
        $app->addRoutingMiddleware();

        $app->addBodyParsingMiddleware();

        // Remove trailing slash (API best practice)
        $app->add(new TrailingSlash(false));

        /** @var RequestIdMiddleware $requestId */
        $requestId = $container->get(RequestIdMiddleware::class);
        $app->add($requestId);

        // Exception Middleware
        /** @var IamExceptionMiddleware $exceptionMw */
        $exceptionMw = $container->get(IamExceptionMiddleware::class);
        $app->add($exceptionMw);

        // Routes
        RoutesProvider::register($app);

        return $app;
    }
}
