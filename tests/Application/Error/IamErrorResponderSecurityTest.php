<?php

declare(strict_types=1);

namespace Tests\Application\Error;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Application\Error\IamErrorResponder;
use Maatify\Exceptions\Application\Error\ErrorContext;
use RuntimeException;

final class IamErrorResponderSecurityTest extends TestCase
{
    public function test_it_does_not_leak_original_message_when_debug_is_false(): void
    {
        $responder = new IamErrorResponder();

        $sensitiveMessage = 'DB password is wrong';

        $throwable = new RuntimeException($sensitiveMessage);

        $context = new ErrorContext(
            traceId: 'trace-sec-1',
            instance: '/secure/test',
            debug: false
        );

        $response = $responder->respond($throwable, $context);

        $body = $response->getBody();

        // Ensure body exists
        $this->assertIsArray($body);

        // Ensure detail field exists
        $this->assertArrayHasKey('detail', $body);

        // Ensure sensitive message is NOT leaked
        $this->assertNotSame($sensitiveMessage, $body['detail']);

        // Also ensure sensitive string does not exist anywhere in body
        $this->assertStringNotContainsString(
            $sensitiveMessage,
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }
}
