<?php

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Maatify\Iam\Application\Security\ClientRequestAuthenticationService;
use Maatify\Iam\Application\Security\DTO\SignedClientRequestDTO;
use Maatify\Iam\Presentation\Http\Security\RequestContext;
use Maatify\Exceptions\Exception\Authentication\UnauthorizedMaatifyException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class ClientAuthMiddleware
{
    private const MAX_BODY_BYTES = 1024 * 1024; // 1MB safety limit

    public function __construct(
        private ClientRequestAuthenticationService $authService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $this->logger->info(
            'Client request received',
            [
                'client_key' => $request->getHeaderLine('X-Client-Key'),
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod()
            ]
        );

        $timestamp = $request->getHeaderLine('X-Request-Timestamp');

        if ($timestamp === '' || !ctype_digit($timestamp)) {
            throw new UnauthorizedMaatifyException();
        }

        $bodyStream = $request->getBody();

        if ($bodyStream->getSize() !== null && $bodyStream->getSize() > self::MAX_BODY_BYTES) {
            throw new UnauthorizedMaatifyException();
        }

        $body = (string) $bodyStream;

        // rewind stream so controllers can read body again
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        $client = $this->authService->authenticate(
            new SignedClientRequestDTO(
                clientKey : $request->getHeaderLine('X-Client-Key'),
                timestamp : $timestamp,
                nonce     : $request->getHeaderLine('X-Request-Nonce'),
                signature : $request->getHeaderLine('X-Request-Signature'),
                method    : $request->getMethod(),
                host      : $request->getUri()->getHost(),
                path      : $request->getUri()->getPath(),
                body      : $body
            )
        );

        $requestIdAttr = $request->getAttribute('request_id');
        $requestId = is_string($requestIdAttr) ? $requestIdAttr : null;

        $context = new RequestContext(
            clientId : $client->id,
            tenantId : $client->tenantId,
            requestId: $requestId,
            ip       : $request->getServerParams()['REMOTE_ADDR'] ?? null
        );

        return $handler->handle(
            $request->withAttribute(RequestContext::class, $context)
        );
    }
}
