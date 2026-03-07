<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 03:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Credential;

use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Repository\ActorCredentialRepositoryInterface;
use Maatify\Iam\Domain\Service\PasswordService;

final readonly class PasswordCredentialStrategy implements CredentialStrategyInterface
{
    public function __construct(
        private ActorCredentialRepositoryInterface $repo,
        private PasswordService $passwordService
    ) {
    }

    public function supports(ProvisionActorDTO $dto): bool
    {
        return $dto->password !== '';
    }

    public function create(int $actorId, ProvisionActorDTO $dto): void
    {
        $hashData = $this->passwordService->hash($dto->password);

        $this->repo->createPasswordCredential(
            $actorId,
            $hashData['hash'],
            $hashData['pepper_id']
        );
    }
}
