<?php

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Maatify\Iam\Application\Security\JsonCanonicalizer;
use Maatify\Iam\Domain\Exception\Conflict\IdempotencyConflictException;
use Maatify\Iam\Domain\Repository\IdempotencyRepositoryInterface;
use Maatify\Iam\Presentation\Http\Security\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class IdempotencyMiddleware
{
    private const MAX_KEY_LENGTH = 128;
    private const WAIT_ATTEMPTS = 10;
    private const WAIT_USLEEP = 20_000; // 20ms → max wait ≈ 200ms

    public function __construct(
        private IdempotencyRepositoryInterface $repo,
        private JsonCanonicalizer $canonicalizer
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

        $canonicalBody = $this->canonicalizer->canonicalize($body);

        $payload = [
            'method' => strtoupper($request->getMethod()),
            'path'   => $request->getUri()->getPath(),
            'query'  => $request->getUri()->getQuery(),
            'body'   => $canonicalBody,
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

        $storedData = json_encode([
            'content_type' => $response->getHeaderLine('Content-Type'),
            'body_base64'  => base64_encode($responseBody)
        ], JSON_THROW_ON_ERROR);

        $this->repo->markDone(
            $context->clientId,
            $key,
            $requestHash,
            $response->getStatusCode(),
            $storedData
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

            if ($existing === null) {
                throw new IdempotencyConflictException();
            }

            if ($existing['request_hash'] !== $requestHash) {
                throw new IdempotencyConflictException();
            }

            if ($existing['status'] === 'DONE') {

                if ($existing['response_body'] === null) {
                    throw new IdempotencyConflictException();
                }

                /** @var array{content_type: string, body_base64: string} $stored */
                $stored = json_decode(
                    $existing['response_body'],
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $replayResponse = new Response((int) $existing['status_code']);

                if ($stored['content_type'] !== '') {
                    $replayResponse = $replayResponse->withHeader(
                        'Content-Type',
                        $stored['content_type']
                    );
                }

                $replayResponse = $replayResponse
                    ->withHeader('Idempotent-Replayed', 'true');

                $decoded = base64_decode($stored['body_base64'], true);

                if ($decoded === false) {
                    throw new IdempotencyConflictException();
                }

                $replayResponse->getBody()->write($decoded);

                return $replayResponse;
            }

            usleep(self::WAIT_USLEEP);
        }

        // Still PROCESSING after bounded wait
        throw new IdempotencyConflictException();
    }
}
