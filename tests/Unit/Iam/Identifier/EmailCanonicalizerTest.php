<?php

declare(strict_types=1);

namespace Tests\Unit\Iam\Identifier;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Domain\Identifier\Canonicalizer\EmailCanonicalizer;

final class EmailCanonicalizerTest extends TestCase
{
    public function test_trims_and_lowercases(): void
    {
        $canonicalizer = new EmailCanonicalizer();

        $this->assertSame(
            'test@mail.com',
            $canonicalizer->canonicalize('  Test@Mail.COM ')
        );
    }

    public function test_is_deterministic(): void
    {
        $c = new EmailCanonicalizer();

        $a = $c->canonicalize('User@Example.COM');
        $b = $c->canonicalize('user@example.com');

        $this->assertSame($a, $b);
    }
}
