<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 04:56
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Repository;

interface ClientSigningSecretRepositoryInterface
{
    /** @return list<array{
     *   cipher:string,
     *   iv:string,
     *   tag:string,
     *   key_id:string,
     *   algorithm:string
     * }>
     */
    public function findActiveByClientId(int $clientId): array;

    public function storeSecret(
        int $clientId,
        string $cipher,
        string $iv,
        string $tag,
        string $keyId,
        string $algorithm
    ): void;
}
