<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Lookup;

interface LookupHmacInterface
{
    /**
     * Returns raw 32-byte binary string.
     */
    public function hash(string $canonical, int $tenantId): string;
}
