<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 01:23
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class TrustedNetworkMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TrustedNetworkMatcherInterface $matcher,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $ip = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');

        if (! $this->matcher->isTrusted($ip)) {
            $response = $this->responseFactory->createResponse(403);
            $response = $response->withHeader('Content-Type', 'application/json');

            $payload = [
                'error'   => 'ACCESS_DENIED',
                'message' => 'Untrusted network',
            ];

            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $json = '{"error":"ACCESS_DENIED","message":"Untrusted network"}';
            }

            $response->getBody()->write($json);

            return $response;
        }

        return $handler->handle($request);
    }
}
