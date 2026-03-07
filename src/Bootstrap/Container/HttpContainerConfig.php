<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 21:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap\Container;

use Maatify\Exceptions\Application\Error\DefaultThrowableToError;
use Maatify\Exceptions\Application\Error\ErrorSerializer;
use Maatify\Exceptions\Application\Format\ProblemDetailsFormatter;
use Maatify\Iam\Bootstrap\Settings;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcher;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcherInterface;
use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class HttpContainerConfig implements ContainerModule
{
    public function register(Container $container, Settings $settings): void
    {
        $container->set(
            ErrorSerializer::class,
            fn () => new ErrorSerializer(
                new DefaultThrowableToError(),
                new ProblemDetailsFormatter()
            )
        );

        $container->set(
            ResponseFactoryInterface::class,
            fn () => new ResponseFactory()
        );

        $container->set(
            TrustedNetworkMatcherInterface::class,
            function (ContainerInterface $c): TrustedNetworkMatcherInterface {

                /** @var Settings $settings*/
                $settings = $c->get(Settings::class);

                return new TrustedNetworkMatcher($settings->trustedIps);
            }
        );
    }
}
