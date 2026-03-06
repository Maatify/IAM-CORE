<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 21:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Bootstrap\Container;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Maatify\Iam\Application\Adapter\CryptoKeyRingEnvAdapter;
use Maatify\Iam\Bootstrap\Settings;
use Maatify\Iam\Domain\Security\Crypto\CryptoKeyRingConfig;
use Maatify\Iam\Domain\Security\Crypto\IAMCryptoContextProvider;
use DI\Container;

final class CryptoContainerConfig implements ContainerModule
{
    public function register(Container $container, Settings $settings): void
    {
        $cryptoRing = CryptoKeyRingConfig::fromEnv(
            CryptoKeyRingEnvAdapter::adapt($settings)
        );

        $container->set(
            ReversibleCryptoAlgorithmRegistry::class,
            function (): ReversibleCryptoAlgorithmRegistry {
                $registry = new ReversibleCryptoAlgorithmRegistry();
                $registry->register(new Aes256GcmAlgorithm());

                return $registry;
            }
        );

        $container->set(
            CryptoContextProviderInterface::class,
            fn() => new IAMCryptoContextProvider()
        );

        $container->set(
            KeyRotationService::class,
            function () use ($cryptoRing, $settings) {

                if ($settings->cryptoActiveKeyId === '') {
                    throw new \RuntimeException('CRYPTO_ACTIVE_KEY_ID is required');
                }

                $activeKeyId = $settings->cryptoActiveKeyId;
                $keys = [];

                foreach ($cryptoRing->keys() as $keyData) {
                    $rawKey = $keyData['key'];

                    if (ctype_xdigit($rawKey) && strlen($rawKey) % 2 === 0) {
                        $decoded = hex2bin($rawKey);
                        if ($decoded === false) {
                            throw new \Exception('Invalid hex crypto key.');
                        }
                        $rawKey = $decoded;
                    }

                    $status = $keyData['id'] === $activeKeyId
                        ? KeyStatusEnum::ACTIVE
                        : KeyStatusEnum::INACTIVE;

                    $keys[] = new CryptoKeyDTO(
                        $keyData['id'],
                        $rawKey,
                        $status,
                        new \DateTimeImmutable()
                    );
                }

                return new KeyRotationService(
                    new InMemoryKeyProvider($keys),
                    new StrictSingleActiveKeyPolicy()
                );
            }
        );

    }
}
