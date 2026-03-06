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

namespace Maatify\Iam\Bootstrap;

use DI\Container;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoContextFactory;
use Maatify\Crypto\DX\CryptoDirectFactory;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Maatify\Exceptions\Application\Error\DefaultThrowableToError;
use Maatify\Exceptions\Application\Error\ErrorSerializer;
use Maatify\Exceptions\Application\Format\ProblemDetailsFormatter;
use Maatify\Iam\Application\Adapter\CryptoKeyRingEnvAdapter;
use Maatify\Iam\Application\Adapter\PasswordPepperEnvAdapter;
use Maatify\Iam\Domain\Security\Crypto\CryptoKeyRingConfig;
use Maatify\Iam\Domain\Security\Crypto\IAMCryptoContextProvider;
use Maatify\Iam\Domain\Security\Password\PasswordPepperRing;
use Maatify\Iam\Domain\Security\Password\PasswordPepperRingConfig;
use Maatify\Iam\Domain\Service\PasswordService;
use Maatify\Iam\Presentation\Http\Middleware\IamExceptionMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\RequestIdMiddleware;
use Maatify\Iam\Presentation\Http\Middleware\TrustedNetworkMiddleware;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcher;
use Maatify\Iam\Presentation\Http\Security\TrustedNetworkMatcherInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class ContainerFactory
{
    public static function build(Settings $settings): Container
    {
        $container = new Container();

        // Settings
        $container->set(Settings::class, $settings);

        $cryptoRing = CryptoKeyRingConfig::fromEnv(
            CryptoKeyRingEnvAdapter::adapt($settings)
        );

        $passwordPepperConfig = PasswordPepperRingConfig::fromEnv(
            PasswordPepperEnvAdapter::adapt($settings)
        );

        // Error Serializer (RFC7807)
        $container->set(ErrorSerializer::class, function () {
            return new ErrorSerializer(
                new DefaultThrowableToError(),
                new ProblemDetailsFormatter()
            );
        });

        $container->set(ResponseFactoryInterface::class, function () {
            return new ResponseFactory();
        });

        // Trusted Network Matcher
        $container->set(
            TrustedNetworkMatcherInterface::class,
            function (Container $c) {
                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                return new TrustedNetworkMatcher($settings->trustedIps);
            }
        );

        // Middlewares
        $container->set(
            IamExceptionMiddleware::class,
            function (Container $c): IamExceptionMiddleware {

                /** @var ErrorSerializer $serializer */
                $serializer = $c->get(ErrorSerializer::class);

                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                /** @var ResponseFactoryInterface $responseFactory */
                $responseFactory = $c->get(ResponseFactoryInterface::class);

                return new IamExceptionMiddleware(
                    $serializer,
                    $settings,
                    $responseFactory
                );
            }
        );

        $container->set(RequestIdMiddleware::class, new RequestIdMiddleware());

        $container->set(
            TrustedNetworkMiddleware::class,
            function (Container $c) {
                /** @var TrustedNetworkMatcherInterface $trustedNetwork*/
                $trustedNetwork = $c->get(TrustedNetworkMatcherInterface::class);

                /** @var ResponseFactoryInterface $responseFactory */
                $responseFactory = $c->get(ResponseFactoryInterface::class);

                return new TrustedNetworkMiddleware(
                    $trustedNetwork,
                    $responseFactory,
                );
            }
        );

        // Crypto
        $container->set(
            ReversibleCryptoAlgorithmRegistry::class,
            function (ContainerInterface $c) {
                $registry = new ReversibleCryptoAlgorithmRegistry();
                $registry->register(new Aes256GcmAlgorithm());
                return $registry;
            }
        );

        $container->set(
            CryptoContextProviderInterface::class,
            function (ContainerInterface $c) {
                return new IAMCryptoContextProvider();
            }
        );

        $container->set(
            KeyRotationService::class,
            function (ContainerInterface $c) use ($cryptoRing) {
                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                $activeKeyId = $settings->cryptoActiveKeyId;
                if ($activeKeyId === '') {
                    throw new \Exception('CRYPTO_ACTIVE_KEY_ID is strictly required.');
                }

                $keys = [];

                // Fail-closed: CRYPTO_KEYS is strictly required (enforced in Config DTO, but double-checked here implicitly)
                foreach ($cryptoRing->keys() as $keyData) {
                    if ($keyData['id'] === '' || $keyData['key'] === '') {
                        throw new \Exception('Invalid crypto key structure. "id" and "key" must be non-empty.');
                    }

                    $rawKey = (string) $keyData['key'];

                    if (ctype_xdigit($rawKey)) {

                        if (strlen($rawKey) % 2 !== 0) {
                            throw new \Exception(
                                'Hex key must have even length for ID: ' . $keyData['id']
                            );
                        }

                        $decoded = hex2bin($rawKey);

                        if ($decoded === false) {
                            throw new \Exception(
                                'Failed to decode hex key for ID: ' . $keyData['id']
                            );
                        }

                        $rawKey = $decoded;
                    }

                    $status = ($keyData['id'] === $activeKeyId)
                        ? KeyStatusEnum::ACTIVE
                        : KeyStatusEnum::INACTIVE;

                    $keys[] = new CryptoKeyDTO(
                        (string) $keyData['id'],
                        (string) $rawKey,
                        $status,
                        new \DateTimeImmutable()
                    );
                }

                // Validate that the active key ID actually exists in the provided keys
                $activeFound = false;
                foreach ($keys as $key) {
                    if ($key->id() === $activeKeyId) {
                        $activeFound = true;
                        break;
                    }
                }

                if (!$activeFound) {
                    throw new \Exception("CRYPTO_ACTIVE_KEY_ID '{$activeKeyId}' not found in CRYPTO_KEYS.");
                }

                // Strict Status Enforcement
                $activeCount = 0;
                foreach ($keys as $key) {
                    if ($key->status() === KeyStatusEnum::ACTIVE) {
                        $activeCount++;
                    }
                }

                if ($activeCount !== 1) {
                    throw new \Exception("Crypto Configuration Error: Exactly ONE active key is required. Found: {$activeCount}");
                }

                $provider = new InMemoryKeyProvider($keys);
                $policy = new StrictSingleActiveKeyPolicy();

                return new KeyRotationService($provider, $policy);
            }
        );

        $container->set(
            HKDFService::class,
            function (ContainerInterface $c) {
                return new HKDFService();
            }
        );

        $container->set(
            CryptoDirectFactory::class,
            function (ContainerInterface $c) {
                /** @var KeyRotationService $rotation */
                $rotation = $c->get(KeyRotationService::class);
                /** @var ReversibleCryptoAlgorithmRegistry $registry */
                $registry = $c->get(ReversibleCryptoAlgorithmRegistry::class);
                return new CryptoDirectFactory($rotation, $registry);
            }
        );

        $container->set(
            CryptoContextFactory::class,
            function (ContainerInterface $c) {
                /** @var KeyRotationService $rotation */
                $rotation = $c->get(KeyRotationService::class);
                /** @var HKDFService $hkdf */
                $hkdf = $c->get(HKDFService::class);
                /** @var ReversibleCryptoAlgorithmRegistry $registry */
                $registry = $c->get(ReversibleCryptoAlgorithmRegistry::class);
                return new CryptoContextFactory($rotation, $hkdf, $registry);
            }
        );

        $container->set(
            CryptoProvider::class,
            function (ContainerInterface $c) {
                /** @var CryptoContextFactory $contextFactory */
                $contextFactory = $c->get(CryptoContextFactory::class);
                /** @var CryptoDirectFactory $directFactory */
                $directFactory = $c->get(CryptoDirectFactory::class);

                return new CryptoProvider($contextFactory, $directFactory);
            }
        );

        $container->set(
            PasswordPepperRing::class,
            function () use ($passwordPepperConfig) {
                return $passwordPepperConfig->ring();
            }
        );

        $container->set(
            PasswordService::class,
            function (ContainerInterface $c) {
                /** @var PasswordPepperRing $ring */
                $ring = $c->get(PasswordPepperRing::class);
                /** @var Settings $settings */
                $settings = $c->get(Settings::class);

                if ($settings->passwordArgon2Options === '') {
                    throw new \Exception('PASSWORD_ARGON2_OPTIONS must be configured.');
                }

                $options = json_decode(
                    $settings->passwordArgon2Options,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                if (!is_array($options)) {
                    throw new \Exception('PASSWORD_ARGON2_OPTIONS must be a valid JSON object.');
                }

                // Validate exact keys
                $requiredKeys = ['memory_cost', 'threads', 'time_cost'];
                $keys = array_keys($options);
                sort($keys);
                sort($requiredKeys);

                if ($keys !== $requiredKeys) {
                    throw new \Exception('PASSWORD_ARGON2_OPTIONS must contain exactly: memory_cost, time_cost, threads.');
                }

                // Validate values
                foreach ($options as $key => $value) {
                    if (!is_int($value) || $value <= 0) {
                        throw new \Exception("PASSWORD_ARGON2_OPTIONS key '$key' must be a positive integer.");
                    }
                }

                /** @var array{memory_cost: int, time_cost: int, threads: int} $options */
                return new PasswordService($ring, $options);
            }
        );

        return $container;
    }
}
