<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Identifier\Canonicalizer;

final class PhoneCanonicalizer
{
    public function canonicalize(string $phone): string
    {
        $phone = trim($phone);

        $hasPlus = str_starts_with($phone, '+');

        // keep digits only
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($hasPlus) {
            return '+' . $digits;
        }

        return $digits;
    }
}
