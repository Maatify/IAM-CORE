<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\DTO;

use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

final readonly class ProvisionActorDTO
{
    /**
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        public string $actorType,
        public IdentifierTypeEnum $identifierType,
        public string $identifier,
        public string $password,
        public ?string $customerMode = null,
        public array $metadata = []
    ) {
    }
}
