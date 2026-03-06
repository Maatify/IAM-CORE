<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 03:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Adapter;

use Maatify\Iam\Bootstrap\Settings;

final class PasswordPepperEnvAdapter
{
    /**
     * @return array<string, mixed>
     */
    public static function adapt(Settings $config): array
    {
        return [
            'PASSWORD_PEPPERS' => $config->passwordPeppers,
            'PASSWORD_ACTIVE_PEPPER_ID' => $config->passwordActivePepperId,
        ];
    }
}
