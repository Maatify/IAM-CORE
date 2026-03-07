<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 21:22
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Response;

use JsonSerializable;
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class JsonResponseFactory
{
    /**
     * @param array<string,mixed>|JsonSerializable $data
     */
    public function data(
        ResponseInterface $response,
        array|JsonSerializable $data,
        int $status = 200
    ): ResponseInterface {

        $response->getBody()->rewind();

        $response->getBody()->write(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withStatus($status);
    }

    /**
     * For Action endpoints (Canonical rule: 204 No Content)
     */
    public function noContent(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withStatus(204);
    }

    /**
     * Optional success payload (legacy support)
     */
    public function success(
        ResponseInterface $response,
        int $status = 200
    ): ResponseInterface {

        return $this->data(
            $response,
            [
                'success' => true,
            ],
            $status
        );
    }

    /**
     * Legacy manual error (Updated to unified envelope)
     *
     * @param array<string, mixed> $meta
     */
    public function error(
        ResponseInterface $response,
        string $message,
        int $status = 400,
        string $code = 'error',
        array $meta = [],
        string $category = 'SYSTEM',
        bool $retryable = false
    ): ResponseInterface {

        $payload = [
            'success' => false,
            'error' => [
                'code'      => $code,
                'category'  => $category,
                'message'   => $message,
                'meta'      => $meta,
                'retryable' => $retryable,
            ],
        ];

        return $this->data($response, $payload, $status);
    }

    /**
     * Canonical Exception-aware error
     */
    public function fromException(
        ResponseInterface $response,
        ApiAwareExceptionInterface $exception
    ): ResponseInterface {

        $message = $exception->isSafe()
            ? $exception->getMessage()
            : 'Internal Server Error';

        $payload = [
            'success' => false,
            'error' => [
                'code'      => $exception->getErrorCode()->getValue(),
                'category'  => $exception->getCategory()->getValue(),
                'message'   => $message,
                'meta'      => $exception->getMeta(),
                'retryable' => $exception->isRetryable(),
            ],
        ];

        return $this->data(
            $response,
            $payload,
            $exception->getHttpStatus()
        );
    }
}
