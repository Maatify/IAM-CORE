<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 21:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap\Container;

use Maatify\Iam\Application\Adapter\PasswordPepperEnvAdapter;
use Maatify\Iam\Bootstrap\Settings;
use Maatify\Iam\Domain\Security\Password\PasswordPepperRing;
use Maatify\Iam\Domain\Security\Password\PasswordPepperRingConfig;
use Maatify\Iam\Domain\Service\PasswordService;
use DI\Container;
use Psr\Container\ContainerInterface;

final class SecurityContainerConfig implements ContainerModule
{
    public function register(Container $container, Settings $settings): void
    {
        $pepperConfig = PasswordPepperRingConfig::fromEnv(
            PasswordPepperEnvAdapter::adapt($settings)
        );

        $container->set(
            PasswordPepperRing::class,
            fn() => $pepperConfig->ring()
        );

        $container->set(
            PasswordService::class,
            function (ContainerInterface $c) use ($settings) {
                $options = json_decode(
                    $settings->passwordArgon2Options,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                /** @var array{memory_cost:int,time_cost:int,threads:int} $options */

                /** @var PasswordPepperRing $passwordPepperRing*/
                $passwordPepperRing = $c->get(PasswordPepperRing::class);

                return new PasswordService(
                    $passwordPepperRing,
                    $options
                );
            }
        );
    }
}
