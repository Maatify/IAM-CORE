<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Lookup;

interface LookupSecretProviderInterface
{
    /**
     * MUST throw on failure.
     */
    public function getSecret(): string;
}
