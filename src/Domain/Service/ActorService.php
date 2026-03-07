<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 02:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Service;

use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Repository\ActorRepositoryInterface;
use Maatify\Iam\Domain\Enum\ActorStatusEnum;

final readonly class ActorService
{
    public function __construct(
        private ActorRepositoryInterface $actorRepo
    ) {
    }

    public function create(
        int $tenantId,
        ProvisionActorDTO $dto
    ): int {
        return $this->actorRepo->insert(
            $tenantId,
            $dto->actorType,
            ActorStatusEnum::ACTIVE->value
        );
    }
}
