<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Identifier\Canonicalizer;

use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

final readonly class IdentifierCanonicalizer
{
    public function __construct(
        private EmailCanonicalizer $email,
        private PhoneCanonicalizer $phone,
    ) {}

    public function canonicalize(
        IdentifierTypeEnum $type,
        string $value
    ): string {
        return match ($type) {
            IdentifierTypeEnum::EMAIL => $this->email->canonicalize($value),
            IdentifierTypeEnum::PHONE => $this->phone->canonicalize($value),
        };
    }
}
