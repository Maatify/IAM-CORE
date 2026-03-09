<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Lookup;

use Maatify\Iam\Domain\Exception\System\CryptoFailureException;

final readonly class LookupHmac implements LookupHmacInterface
{
    public function __construct(
        private LookupSecretProviderInterface $secretProvider,
    ) {
    }

    public function hash(string $canonical, int $tenantId): string
    {
        $secret = $this->secretProvider->getSecret();

        if ($secret === '') {
            throw new CryptoFailureException('IAM lookup secret unavailable');
        }

        return hash_hmac(
            'sha256',
            $tenantId . ':' . $canonical,
            $secret,
            true // raw binary
        );
    }
}
