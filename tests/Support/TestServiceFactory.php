<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 20:56
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Support;

use PDO;

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

use Maatify\Iam\Application\Service\ProvisionActorService;

use Maatify\Iam\Domain\Identifier\Canonicalizer\EmailCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\PhoneCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;

use Maatify\Iam\Domain\Security\Credential\CredentialStrategyResolver;
use Maatify\Iam\Domain\Security\Credential\PasswordCredentialStrategy;

use Maatify\Iam\Domain\Security\Crypto\IAMCryptoContextProvider;
use Maatify\Iam\Domain\Security\Crypto\IdentifierCryptoContextResolver;

use Maatify\Iam\Domain\Security\Lookup\LookupHmac;

use Maatify\Iam\Domain\Security\Password\PasswordPepperRing;

use Maatify\Iam\Domain\Service\ActorService;
use Maatify\Iam\Domain\Service\IdentifierService;
use Maatify\Iam\Domain\Service\CredentialService;
use Maatify\Iam\Domain\Service\PasswordService;

use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorIdentifierRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorCredentialRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoTransactionManager;

use Tests\Support\Security\EnvLookupSecretProvider;

final class TestServiceFactory
{
    public static function provisionActorService(PDO $pdo): ProvisionActorService
    {
        $canonicalizer = new IdentifierCanonicalizer(
            new EmailCanonicalizer(),
            new PhoneCanonicalizer()
        );

        $lookupHmac = new LookupHmac(
            new EnvLookupSecretProvider()
        );

        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $keys = [
            new CryptoKeyDTO(
                id       : 'v1',
                material : '12345678901234567890123456789012',
                status   : KeyStatusEnum::ACTIVE,
                createdAt: new \DateTimeImmutable()
            )
        ];

        $keyProvider = new InMemoryKeyProvider($keys);

        $keyRotation = new KeyRotationService(
            $keyProvider,
            new StrictSingleActiveKeyPolicy()
        );

        $contextFactory = new CryptoContextFactory(
            $keyRotation,
            new HKDFService(),
            $registry
        );

        $directFactory = new CryptoDirectFactory(
            $keyRotation,
            $registry
        );

        $crypto = new CryptoProvider(
            $contextFactory,
            $directFactory
        );

        $contextResolver = new IdentifierCryptoContextResolver(
            new IAMCryptoContextProvider()
        );

        $pepperRing = new PasswordPepperRing(
            ['v1' => 'test-pepper-secret-12345678901234567890'],
            'v1'
        );

        $passwordService = new PasswordService(
            $pepperRing,
            [
                'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                'time_cost'   => PASSWORD_ARGON2_DEFAULT_TIME_COST,
                'threads'     => PASSWORD_ARGON2_DEFAULT_THREADS
            ]
        );

        $actorRepo = new PdoActorRepository($pdo);
        $identifierRepo = new PdoActorIdentifierRepository($pdo);
        $credentialRepo = new PdoActorCredentialRepository($pdo);

        $actorService = new ActorService($actorRepo);

        $identifierService = new IdentifierService(
            $identifierRepo,
            $canonicalizer,
            $lookupHmac,
            $crypto,
            $contextResolver
        );

        $passwordStrategy = new PasswordCredentialStrategy(
            $credentialRepo,
            $passwordService
        );

        $resolver = new CredentialStrategyResolver(
            $passwordStrategy
        );

        $credentialService = new CredentialService($resolver);

        return new ProvisionActorService(
            $actorService,
            $identifierService,
            $credentialService,
            new PdoTransactionManager($pdo)
        );
    }
}
