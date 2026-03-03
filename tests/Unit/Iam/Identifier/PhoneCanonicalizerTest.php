<?php

declare(strict_types=1);

namespace Tests\Unit\Iam\Identifier;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Domain\Identifier\Canonicalizer\PhoneCanonicalizer;

final class PhoneCanonicalizerTest extends TestCase
{
    public function test_preserves_plus_and_removes_noise(): void
    {
        $canonicalizer = new PhoneCanonicalizer();

        $this->assertSame(
            '+201012345678',
            $canonicalizer->canonicalize(' +20 10-123 456-78 ')
        );
    }

    public function test_without_plus(): void
    {
        $canonicalizer = new PhoneCanonicalizer();

        $this->assertSame(
            '0101234567',
            $canonicalizer->canonicalize(' 010-123-4567 ')
        );
    }

    public function test_is_deterministic(): void
    {
        $canonicalizer = new PhoneCanonicalizer();

        $a = $canonicalizer->canonicalize('+20 10 123 456 78');
        $b = $canonicalizer->canonicalize('+201012345678');

        $this->assertSame($a, $b);
    }
}
