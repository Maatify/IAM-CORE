<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security\DTO;

final readonly class SignedClientRequestDTO
{
    public function __construct(
        public string $clientKey,
        public string $timestamp,
        public string $nonce,
        public string $signature,
        public string $method,
        public string $host,
        public string $path,
        public string $body
    ) {
    }
}
