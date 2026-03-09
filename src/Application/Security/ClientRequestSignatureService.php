<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

final class ClientRequestSignatureService
{
    public function buildPayload(
        string $clientKey,
        string $method,
        string $host,
        string $path,
        string $timestamp,
        string $nonce,
        string $body
    ): string {

        $normalizedBody = $this->canonicalizeJson($body);

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

    private function canonicalizeJson(string $body): string
    {
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return $body;
        }

        $this->ksortRecursive($decoded);

        return json_encode(
            $decoded,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
            | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    private function ksortRecursive(array &$array): void
    {
        if ($this->isAssoc($array)) {
            ksort($array);
        }

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    /**
     * @param array<string,mixed> $array
     */
    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
