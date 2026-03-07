<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 03:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Credential;

use RuntimeException;
use Maatify\Iam\Domain\DTO\ProvisionActorDTO;

final class CredentialStrategyResolver
{
    /** @var CredentialStrategyInterface[] */
    private array $strategies;

    public function __construct(
        CredentialStrategyInterface ...$strategies
    ) {
        $this->strategies = $strategies;
    }

    public function resolve(ProvisionActorDTO $dto): CredentialStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($dto)) {
                return $strategy;
            }
        }

        throw new RuntimeException('No credential strategy found');
    }
}
