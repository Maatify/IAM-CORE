<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 00:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Middleware;

use Maatify\Exceptions\Application\Error\ErrorContext;
use Maatify\Exceptions\Application\Error\ErrorSerializer;
use Maatify\Iam\Bootstrap\Settings;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class IamExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ErrorSerializer $serializer,
        private Settings $settings,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\Throwable $t) {

            $rawTraceId = $request->getAttribute('request_id');

            $traceId = is_string($rawTraceId) ? $rawTraceId : null;

            $context = new ErrorContext(
                traceId : $traceId,
                instance: (string) $request->getUri(),
                debug   : $this->settings->debug
            );

            $errorResponse = $this->serializer->serialize($t, $context);

            $response = $this->responseFactory
                ->createResponse($errorResponse->getStatus());

            foreach ($errorResponse->getHeaders() as $name => $value) {
                $response = $response->withHeader($name, $value);
            }

            $response = $response->withHeader(
                'Content-Type',
                $errorResponse->getContentType()
            );

            $response->getBody()->write(
                json_encode(
                    $errorResponse->getBody(),
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )
            );

            return $response;
        }
    }
}
