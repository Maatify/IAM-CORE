<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $externalId = trim($request->getHeaderLine('X-Request-Id'));

        $internalId = Uuid::uuid4()->toString();

        $request = $request
            ->withAttribute('request_id', $internalId)
            ->withAttribute(
                'external_request_id',
                Uuid::isValid($externalId) ? $externalId : null
            );

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Request-Id', $internalId)
            ->withHeader('X-Server-Time', (string)time());
    }
}
