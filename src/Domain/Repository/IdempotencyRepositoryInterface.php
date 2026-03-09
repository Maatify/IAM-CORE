<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Repository;

interface IdempotencyRepositoryInterface
{
    /**
     * @return array{
     *     request_hash:string,
     *     status:string,
     *     response_body:string|null,
     *     status_code:int|null,
     *     processing_expires_at:string|null
     * }|null
     */
    public function find(int $clientId, string $key): ?array;

    /**
     * Attempts to claim execution of an idempotent request.
     *
     * Returns true if the caller owns the execution lock.
     * Returns false if another request already owns it or
     * a completed response exists.
     */
    public function claimProcessing(
        int $clientId,
        string $key,
        string $requestHash
    ): bool;

    /**
     * Marks request as completed and stores replay response.
     */
    public function markDone(
        int $clientId,
        string $key,
        string $requestHash,
        int $status,
        string $responseBody
    ): void;
}
