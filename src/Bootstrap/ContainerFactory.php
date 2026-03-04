<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap;

use DI\Container;
use Maatify\Exceptions\Application\Error\DefaultThrowableToError;
use Maatify\Exceptions\Application\Error\ErrorSerializer;
use Maatify\Exceptions\Application\Format\ProblemDetailsFormatter;
use Maatify\Iam\Presentation\Http\Middleware\IamExceptionMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\RequestIdMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\TrustedNetworkMiddleware;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcher;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class ContainerFactory
{
    public static function build(Settings $settings): Container
    {
        $container = new Container();

        // Settings
        $container->set(Settings::class, $settings);

        // Error Serializer (RFC7807)
        $container->set(ErrorSerializer::class, function () {
            return new ErrorSerializer(
                new DefaultThrowableToError(),
                new ProblemDetailsFormatter()
            );
        });

        $container->set(ResponseFactoryInterface::class, function () {
            return new ResponseFactory();
        });

        // Trusted Network Matcher
        $container->set(
            TrustedNetworkMatcherInterface::class,
            function (Container $c) {
                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                return new TrustedNetworkMatcher($settings->trustedIps);
            }
        );

        // Middlewares
        $container->set(
            IamExceptionMiddleware::class,
            function (Container $c): IamExceptionMiddleware {

                /** @var ErrorSerializer $serializer */
                $serializer = $c->get(ErrorSerializer::class);

                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                /** @var ResponseFactoryInterface $responseFactory */
                $responseFactory = $c->get(ResponseFactoryInterface::class);

                return new IamExceptionMiddleware(
                    $serializer,
                    $settings,
                    $responseFactory
                );
            }
        );

        $container->set(RequestIdMiddleware::class, new RequestIdMiddleware());

        $container->set(
            TrustedNetworkMiddleware::class,
            function (Container $c) {
                /** @var TrustedNetworkMatcherInterface $trustedNetwork*/
                $trustedNetwork = $c->get(TrustedNetworkMatcherInterface::class);

                /** @var ResponseFactoryInterface $responseFactory */
                $responseFactory = $c->get(ResponseFactoryInterface::class);

                return new TrustedNetworkMiddleware(
                    $trustedNetwork,
                    $responseFactory,
                );
            }
        );

        return $container;
    }
}
