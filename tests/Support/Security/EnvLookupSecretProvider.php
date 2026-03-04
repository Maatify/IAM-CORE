<?php

declare(strict_types=1);

namespace Tests\Support\Security;

use Maatify\Iam\Domain\Security\Lookup\LookupSecretProviderInterface;
use RuntimeException;

final readonly class EnvLookupSecretProvider implements LookupSecretProviderInterface
{
    public function getSecret(): string
    {
        $secret = (string)($_ENV['EMAIL_BLIND_INDEX_KEY'] ?? '');
        if ($secret === '') {
            throw new RuntimeException('EMAIL_BLIND_INDEX_KEY is missing for tests.');
        }

        return $secret;
    }
}
