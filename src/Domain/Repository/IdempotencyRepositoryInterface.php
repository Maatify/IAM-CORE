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
     *     status_code:int|null
     * }|null
     */
    public function find(int $clientId, string $key): ?array;

    public function claimProcessing(
        int $clientId,
        string $key,
        string $requestHash
    ): bool;

    public function markDone(
        int $clientId,
        string $key,
        string $requestHash,
        int $status,
        string $responseBody
    ): void;
}
