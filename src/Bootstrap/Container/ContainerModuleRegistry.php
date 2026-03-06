<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 23:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap\Container;

use DI\Container;
use Maatify\Iam\Bootstrap\Settings;

final class ContainerModuleRegistry
{
    /**
     * @return list<ContainerModule>
     */
    public static function discover(): array
    {
        return [
            new HttpContainerConfig(),
            new CryptoContainerConfig(),
            new SecurityContainerConfig(),
        ];
    }

    public static function registerAll(Container $container, Settings $settings): void
    {
        foreach (self::discover() as $module) {
            $module->register($container, $settings);
        }
    }
}
