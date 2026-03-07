<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 02:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Service;

use Maatify\Iam\Application\Contract\TransactionManagerInterface;
use Maatify\Iam\Application\Support\ExceptionMapper;
use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Service\ActorService;
use Maatify\Iam\Domain\Service\IdentifierService;
use Maatify\Iam\Domain\Service\CredentialService;

final readonly class ProvisionActorService
{
    public function __construct(
        private ActorService $actorService,
        private IdentifierService $identifierService,
        private CredentialService $credentialService,
        private TransactionManagerInterface $transaction
    ) {
    }

    public function execute(
        int $tenantId,
        ProvisionActorDTO $dto
    ): int {

        try {

            return $this->transaction->transactional(function () use ($tenantId, $dto) {

                $actorId = $this->actorService->create(
                    $tenantId,
                    $dto
                );

                $this->identifierService->create(
                    $actorId,
                    $tenantId,
                    $dto
                );

                $this->credentialService->create(
                    $actorId,
                    $dto
                );

                return $actorId;
            });

        } catch (\Throwable $e) {

            throw ExceptionMapper::map($e);
        }
    }
}
