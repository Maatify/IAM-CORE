<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

final readonly class ClientRequestSignatureService
{
    public function __construct(
        private JsonCanonicalizer $canonicalizer
    ) {
    }

    public function buildPayload(
        string $clientKey,
        string $method,
        string $host,
        string $path,
        string $timestamp,
        string $nonce,
        string $body
    ): string {

        $normalizedBody = $this->canonicalizer->canonicalize($body);

        $bodyHash = hash('sha256', $normalizedBody);

        $host = strtolower($host);
        $path = '/' . trim($path, '/');

        $canonical =
            strtoupper($method) . "\n"
            . $host . "\n"
            . $path . "\n"
            . $timestamp . "\n"
            . $nonce . "\n"
            . $bodyHash;

        return $clientKey . "\n" . hash('sha256', $canonical);
    }

    public function compute(string $payload, string $secret): string
    {
        return base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );
    }

    public function verify(string $provided, string $expected): bool
    {
        return hash_equals($expected, $provided);
    }
}
