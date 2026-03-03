<?php

declare(strict_types=1);

namespace Tests\Unit\Iam\Identifier;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Domain\Identifier\Canonicalizer\EmailCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\PhoneCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

final class IdentifierCanonicalizerTest extends TestCase
{
    public function test_dispatch_email(): void
    {
        $canonicalizer = new IdentifierCanonicalizer(
            new EmailCanonicalizer(),
            new PhoneCanonicalizer()
        );

        $this->assertSame(
            'test@example.com',
            $canonicalizer->canonicalize(
                IdentifierTypeEnum::EMAIL,
                ' TEST@EXAMPLE.COM '
            )
        );
    }

    public function test_dispatch_phone(): void
    {
        $canonicalizer = new IdentifierCanonicalizer(
            new EmailCanonicalizer(),
            new PhoneCanonicalizer()
        );

        $this->assertSame(
            '+1234567890',
            $canonicalizer->canonicalize(
                IdentifierTypeEnum::PHONE,
                ' +1 234-567-890 '
            )
        );
    }
}
