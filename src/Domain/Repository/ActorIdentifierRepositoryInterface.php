<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-03 22:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Repository;

interface ActorIdentifierRepositoryInterface
{
    public function existsByLookup(
        int $tenantId,
        string $actorType,
        string $identifierType,
        string $lookupHashBinary32
    ): bool;

    public function insert(
        int $actorId,
        int $tenantId,
        string $actorType,
        string $identifierType,
        string $lookupHashBinary32,
        string $cipher,
        string $iv,
        string $tag,
        string $keyId,
        string $algorithm,
        bool $isVerified = false
    ): void;
}
