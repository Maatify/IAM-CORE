<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 02:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Service;

use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Security\Credential\CredentialStrategyResolver;

final readonly class CredentialService
{
    public function __construct(
        private CredentialStrategyResolver $resolver
    ) {
    }

    public function create(
        int $actorId,
        ProvisionActorDTO $dto
    ): void {

        $strategy = $this->resolver->resolve($dto);

        $strategy->create($actorId, $dto);
    }
}
