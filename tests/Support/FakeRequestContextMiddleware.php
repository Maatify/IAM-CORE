<?php

declare(strict_types=1);

namespace Tests\Support;

use Maatify\Iam\Presentation\Http\Security\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FakeRequestContextMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $context = new RequestContext(
            clientId: 1,
            tenantId: 1,
            requestId: 'test-request-id',
            ip: '127.0.0.1'
        );

        $request = $request->withAttribute(RequestContext::class, $context);

        return $handler->handle($request);
    }
}
