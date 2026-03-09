<?php

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Maatify\Iam\Domain\Exception\Conflict\IdempotencyConflictException;
use Maatify\Iam\Domain\Repository\IdempotencyRepositoryInterface;
use Maatify\Iam\Presentation\Http\Response\JsonResponseFactory;
use Maatify\Iam\Presentation\Http\Security\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class IdempotencyMiddleware
{
    private const MAX_KEY_LENGTH = 128;
    private const WAIT_ATTEMPTS = 20;
    private const WAIT_USLEEP = 50_000; // 50ms => total ~1s

    public function __construct(
        private IdempotencyRepositoryInterface $repo,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return $handler->handle($request);
        }

        $key = $request->getHeaderLine('Idempotency-Key');

        if ($key === '') {
            return $handler->handle($request);
        }

        if (strlen($key) > self::MAX_KEY_LENGTH) {
            throw new IdempotencyConflictException();
        }

        $context = $request->getAttribute(RequestContext::class);

        if (!$context instanceof RequestContext) {
            return $handler->handle($request);
        }

        $body = (string) $request->getBody();
        if ($request->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        $payload = [
            'method' => strtoupper($request->getMethod()),
            'path'   => $request->getUri()->getPath(),
            'query'  => $request->getUri()->getQuery(),
            'body'   => $body,
        ];

        $requestHash = hash(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        // Step 1: try to claim ownership before executing controller
        $claimed = $this->repo->claimProcessing(
            $context->clientId,
            $key,
            $requestHash
        );

        if (!$claimed) {
            return $this->waitForExistingResult(
                $context->clientId,
                $key,
                $requestHash
            );
        }

        // Step 2: only the owner executes the controller once
        $response = $handler->handle($request);

        $responseBody = (string) $response->getBody();

        // Restore body for downstream safety if seekable
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        $this->repo->markDone(
            $context->clientId,
            $key,
            $requestHash,
            $response->getStatusCode(),
            $responseBody
        );

        return $response;
    }

    private function waitForExistingResult(
        int $clientId,
        string $key,
        string $requestHash
    ): ResponseInterface {
        for ($i = 0; $i < self::WAIT_ATTEMPTS; $i++) {
            $existing = $this->repo->find($clientId, $key);

            if ($existing !== null) {
                if ($existing['request_hash'] !== $requestHash) {
                    throw new IdempotencyConflictException();
                }

                if ($existing['status'] === 'DONE') {

                    if ($existing['response_body'] === null) {
                        throw new IdempotencyConflictException();
                    }

                    /** @var array<string,mixed> $body */
                    $body = json_decode(
                        $existing['response_body'],
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    return $this->json
                        ->data(
                            new Response(),
                            $body,
                            (int) $existing['status_code']
                        )
                        ->withHeader('Idempotent-Replayed', 'true');
                }
            }

            usleep(self::WAIT_USLEEP);
        }

        throw new IdempotencyConflictException();
    }
}
