<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:12
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap;

final readonly class Settings
{
    /**
     * @param list<string> $trustedIps
     */
    public function __construct(
        public string $appName,
        public string $appEnv,
        public bool $debug,
        public string $version,
        public array $trustedIps,

    ) {
    }

    public static function fromEnv(): self
    {
        $trusted = $_ENV['IAM_TRUSTED_IPS'] ?? '';
        $trustedIps = self::parseCsvList($trusted);
        return new self(
            appName: $_ENV['APP_NAME'] ?? 'maatify-iam',
            appEnv : $_ENV['APP_ENV'] ?? 'production',
            debug  : filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
            version: $_ENV['APP_VERSION'] ?? '1.0.0',
            trustedIps : $trustedIps,
        );
    }

    /**
     * @return list<string>
     */
    private static function parseCsvList(string $csv): array
    {
        if (trim($csv) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $csv));
        $parts = array_values(array_filter($parts, static fn(string $v): bool => $v !== ''));

        return $parts;
    }
}
