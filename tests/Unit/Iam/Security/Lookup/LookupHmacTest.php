<?php

declare(strict_types=1);

namespace Tests\Unit\Iam\Security\Lookup;

use PHPUnit\Framework\TestCase;
use Maatify\Iam\Domain\Security\Lookup\LookupHmac;
use Maatify\Iam\Domain\Security\Lookup\LookupSecretProviderInterface;
use Maatify\Iam\Domain\Exception\System\CryptoFailureException;

final class LookupHmacTest extends TestCase
{
    private function provider(string $secret): LookupSecretProviderInterface
    {
        return new class($secret) implements LookupSecretProviderInterface {
            public function __construct(private string $secret) {}
            public function getSecret(): string
            {
                return $this->secret;
            }
        };
    }

    public function test_returns_32_byte_binary_hash(): void
    {
        $hmac = new LookupHmac($this->provider('secret'));

        $hash = $hmac->hash('test@example.com');

        $this->assertSame(32, strlen($hash));
    }

    public function test_same_input_same_hash(): void
    {
        $hmac = new LookupHmac($this->provider('secret'));

        $a = $hmac->hash('value');
        $b = $hmac->hash('value');

        $this->assertSame($a, $b);
    }

    public function test_different_input_different_hash(): void
    {
        $hmac = new LookupHmac($this->provider('secret'));

        $a = $hmac->hash('a');
        $b = $hmac->hash('b');

        $this->assertNotSame($a, $b);
    }

    public function test_different_secret_different_hash(): void
    {
        $hmac1 = new LookupHmac($this->provider('secret1'));
        $hmac2 = new LookupHmac($this->provider('secret2'));

        $this->assertNotSame(
            $hmac1->hash('value'),
            $hmac2->hash('value')
        );
    }

    public function test_empty_secret_throws(): void
    {
        $this->expectException(CryptoFailureException::class);

        $hmac = new LookupHmac($this->provider(''));
        $hmac->hash('value');
    }
}
