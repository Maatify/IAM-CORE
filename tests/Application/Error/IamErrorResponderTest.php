<?php

declare(strict_types=1);

namespace Tests\Application\Error;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Application\Error\IamErrorResponder;
use Maatify\Exceptions\Application\Error\ErrorContext;
use Maatify\Exceptions\Application\Error\ErrorResponseModel;
use RuntimeException;

final class IamErrorResponderTest extends TestCase
{
    public function test_it_serializes_throwable_deterministically(): void
    {
        $responder = new IamErrorResponder();

        $throwable = new RuntimeException('Unexpected failure');

        $context = new ErrorContext(
            traceId: 'trace-123',
            instance: '/test/endpoint',
            debug: false
        );

        $responseA = $responder->respond($throwable, $context);
        $responseB = $responder->respond($throwable, $context);

        $this->assertInstanceOf(ErrorResponseModel::class, $responseA);

        // Deterministic
        $this->assertEquals($responseA, $responseB);

        // Status
        $this->assertIsInt($responseA->getStatus());

        // RFC7807 content-type
        $this->assertSame(
            'application/problem+json; charset=utf-8',
            $responseA->getContentType()
        );

        // Body structure
        $body = $responseA->getBody();

        $this->assertIsArray($body);
        $this->assertArrayHasKey('type', $body);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('detail', $body);
    }
}
