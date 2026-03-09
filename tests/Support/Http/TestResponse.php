<?php

declare(strict_types=1);

namespace Tests\Support\Http;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

final readonly class TestResponse
{
    public function __construct(
        private ResponseInterface $response
    ) {
    }

    public function assertStatus(int $status): self
    {
        Assert::assertSame(
            $status,
            $this->response->getStatusCode()
        );

        return $this;
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertNoContent(): self
    {
        return $this->assertStatus(204);
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $data = json_decode(
            (string) $this->response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        /** @var array<string, mixed> $data */
        return $data;
    }

    public function assertJsonHas(string $key): self
    {
        $data = $this->json();

        Assert::assertArrayHasKey($key, $data);

        return $this;
    }

    /**
     * @param array<int, string> $structure
     */
    public function assertJsonStructure(array $structure): self
    {
        $data = $this->json();

        foreach ($structure as $key) {
            Assert::assertArrayHasKey($key, $data);
        }

        return $this;
    }

    public function assertJsonValue(string $key, mixed $value): self
    {
        $data = $this->json();

        Assert::assertSame(
            $value,
            $data[$key] ?? null
        );

        return $this;
    }

    public function raw(): ResponseInterface
    {
        return $this->response;
    }
}
