<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Identifier\Canonicalizer;

final class EmailCanonicalizer
{
    public function canonicalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
