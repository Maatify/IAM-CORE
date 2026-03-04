<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\DTO;

use Maatify\Iam\Domain\Enum\ActorStatusEnum;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

final readonly class CreateActorCommandDTO
{
    public function __construct(
        public int $tenantId,
        public string $actorType,
        public IdentifierTypeEnum $identifierType,
        public string $rawIdentifier,
        public ActorStatusEnum $status = ActorStatusEnum::ACTIVE,
    ) {
    }
}
