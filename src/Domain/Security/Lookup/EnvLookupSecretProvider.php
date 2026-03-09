<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 23:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Lookup;

final readonly class EnvLookupSecretProvider implements LookupSecretProviderInterface
{
    public function __construct(
        private string $secret
    ) {
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
