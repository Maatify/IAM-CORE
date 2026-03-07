<?php

declare(strict_types=1);

namespace Tests\Integration;

use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Slim\Psr7\Headers;
use Tests\Support\TestDatabaseManager;
use Tests\Support\TestAppFactory;
use Tests\Support\Http\TestResponse;

abstract class HttpTestCase extends IntegrationTestCase
{
    /** @var App<\Psr\Container\ContainerInterface|null> */
    protected App $app;

    /** @var array<string,string> */
    private array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = TestDatabaseManager::connection();

        $this->app = TestAppFactory::create($pdo);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function postJson(string $uri, array $payload): TestResponse
    {
        return $this->request('POST', $uri, $payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function putJson(string $uri, array $payload): TestResponse
    {
        return $this->request('PUT', $uri, $payload);
    }

    protected function getJson(string $uri): TestResponse
    {
        return $this->request('GET', $uri);
    }

    protected function deleteJson(string $uri): TestResponse
    {
        return $this->request('DELETE', $uri);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    private function request(
        string $method,
        string $uri,
        ?array $payload = null
    ): TestResponse {

        $uriObj = new Uri('', '', 80, $uri);

        $headers = new Headers(
            array_merge(
                ['Content-Type' => 'application/json'],
                $this->headers
            )
        );

        $resource = fopen('php://temp', 'r+');

        if ($resource === false) {
            throw new \RuntimeException('Failed to open stream');
        }

        if ($payload !== null) {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
            fwrite($resource, $json);
        }

        rewind($resource);

        $stream = new Stream($resource);

        $request = new Request(
            $method,
            $uriObj,
            $headers,
            [],
            [],
            $stream
        );

        if ($payload !== null) {
            $request = $request->withParsedBody($payload);
        }

        $response = $this->app->handle($request);

        $this->headers = []; // reset headers

        return new TestResponse($response);
    }
}
