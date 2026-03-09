<?php

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\IamClient;

use Maatify\Iam\Application\Security\ClientRequestSignatureService;
use Maatify\Iam\Application\Security\JsonCanonicalizer;
use RuntimeException;

final class IamClient
{
    private ClientRequestSignatureService $signatureService;
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientKey,
        private readonly string $secret,
        ?ClientRequestSignatureService $signatureService = null
    ) {
        $this->signatureService = $signatureService ?? new ClientRequestSignatureService(new JsonCanonicalizer());
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function createActor(array $data): array
    {
        return $this->request(
            'POST',
            '/internal/actors',
            $data
        );
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function request(
        string $method,
        string $path,
        array $data
    ): array {

        $url = $this->baseUrl . $path;

        $host = parse_url($this->baseUrl, PHP_URL_HOST);

        if (!is_string($host)) {
            throw new RuntimeException('Invalid base URL');
        }

        $timestamp = time();
        $nonce = bin2hex(random_bytes(12));
        $idempotency = bin2hex(random_bytes(16));

        $body = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $path = '/' . ltrim($path, '/');

        $payload = $this->signatureService->buildPayload(
            $this->clientKey,
            $method,
            $host,
            $path,
            (string)$timestamp,
            $nonce,
            $body
        );

        $signature = $this->signatureService->compute(
            $payload,
            $this->secret
        );

        $headers = [
            'Content-Type: application/json',
            "X-Client-Key: {$this->clientKey}",
            "Host: $host",
            "X-Request-Timestamp: $timestamp",
            "X-Request-Nonce: $nonce",
            "X-Request-Signature: $signature",
            "Idempotency-Key: $idempotency"
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);

        $attempts = 0;
        $maxAttempts = 3;

        do {

            $response = curl_exec($ch);

            if ($response === false) {
                $attempts++;
                usleep(100000 * $attempts); // backoff
                continue;
            }

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($status >= 500 && $status <= 599) {
                $attempts++;
                usleep(100000 * $attempts);
                continue;
            }

            break;

        } while ($attempts < $maxAttempts);

        if (!is_string($response)) {
            throw new RuntimeException('IAM request failed');
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $decoded = json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid IAM response');
        }

        if ($status >= 400) {
            throw new RuntimeException('IAM error: ' . $response);
        }

        return $decoded;
    }
}
