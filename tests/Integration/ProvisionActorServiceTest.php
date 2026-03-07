<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 03:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Integration;

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

use Maatify\Iam\Domain\DTO\ProvisionActorDTO;

use Maatify\Iam\Domain\Identifier\Canonicalizer\EmailCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\PhoneCanonicalizer;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;

use Maatify\Iam\Domain\Security\Credential\CredentialStrategyResolver;
use Maatify\Iam\Domain\Security\Credential\PasswordCredentialStrategy;
use Maatify\Iam\Domain\Security\Crypto\IAMCryptoContextProvider;
use Maatify\Iam\Domain\Security\Crypto\IdentifierCryptoContextResolver;

use Maatify\Iam\Domain\Security\Lookup\LookupHmac;

use Maatify\Iam\Domain\Security\Password\PasswordPepperRing;

use Maatify\Iam\Domain\Service\ActorService;
use Maatify\Iam\Domain\Service\CredentialService;
use Maatify\Iam\Domain\Service\IdentifierService;
use Maatify\Iam\Domain\Service\PasswordService;

use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorCredentialRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorIdentifierRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoTransactionManager;

use Tests\Support\Security\EnvLookupSecretProvider;
use Tests\Support\TestDatabaseManager;

final class ProvisionActorServiceTest extends IntegrationTestCase
{
    public function test_creates_actor_and_identifier_encrypted_with_unique_lookup(): void
    {
        $pdo = TestDatabaseManager::connection();

        /*
        |----------------------------------------------------
        | Seed Tenant (FK requirement)
        |----------------------------------------------------
        */
        $pdo->exec("
            INSERT INTO iam_tenants (`key`, name, status, metadata_json, primary_identifier_type)
            VALUES ('t1', 'Tenant 1', 'ACTIVE', NULL, 'EMAIL')
        ");

        $tenantId = (int)$pdo->lastInsertId();

        /*
        |----------------------------------------------------
        | Canonicalization
        |----------------------------------------------------
        */
        $canonicalizer = new IdentifierCanonicalizer(
            new EmailCanonicalizer(),
            new PhoneCanonicalizer()
        );

        /*
        |----------------------------------------------------
        | Lookup HMAC
        |----------------------------------------------------
        */
        $lookupHmac = new LookupHmac(
            new EnvLookupSecretProvider()
        );

        /*
        |----------------------------------------------------
        | Crypto Provider
        |----------------------------------------------------
        */
        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $cryptoKeysJson = (string)($_ENV['CRYPTO_KEYS'] ?? '[]');
        $activeKeyId = (string)($_ENV['CRYPTO_ACTIVE_KEY_ID'] ?? 'v1');

        /** @var array<int,array{id:string,key:string}> $decoded */
        $decoded = json_decode($cryptoKeysJson, true, 512, JSON_THROW_ON_ERROR);

        $keys = [];

        foreach ($decoded as $item) {

            $id = (string)$item['id'];
            $material = (string)$item['key'];

            $keys[] = new CryptoKeyDTO(
                id: $id,
                material: $material,
                status: $id === $activeKeyId
                    ? KeyStatusEnum::ACTIVE
                    : KeyStatusEnum::INACTIVE,
                createdAt: new \DateTimeImmutable('2026-03-04 00:00:00')
            );
        }

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

        /*
        |----------------------------------------------------
        | Password Security
        |----------------------------------------------------
        */
        $pepperRing = new PasswordPepperRing(
            [
                'v1' => 'test-pepper-secret-12345678901234567890'
            ],
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

        /*
        |----------------------------------------------------
        | Repositories
        |----------------------------------------------------
        */
        $actorRepo = new PdoActorRepository($pdo);

        $identifierRepo = new PdoActorIdentifierRepository($pdo);

        $credentialRepo = new PdoActorCredentialRepository($pdo);

        /*
        |----------------------------------------------------
        | Domain Services
        |----------------------------------------------------
        */
        $actorService = new ActorService(
            $actorRepo
        );

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

        $credentialService = new CredentialService(
            $resolver
        );

        /*
        |----------------------------------------------------
        | Application Service
        |----------------------------------------------------
        */
        $service = new ProvisionActorService(
            $actorService,
            $identifierService,
            $credentialService,
            new PdoTransactionManager($pdo)
        );

        /*
        |----------------------------------------------------
        | Execute Use Case
        |----------------------------------------------------
        */
        $actorId = $service->execute(
            $tenantId,
            new ProvisionActorDTO(
                actorType: 'customer',
                identifierType: IdentifierTypeEnum::EMAIL,
                identifier: '  TEST@Example.com ',
                password: 'password',
                customerMode: 'standard',
                metadata: []
            )
        );

        $this->assertGreaterThan(0, $actorId);

        /*
        |----------------------------------------------------
        | Ensure identifier encrypted correctly
        |----------------------------------------------------
        */
        $stmt = $pdo->prepare(
            'SELECT * FROM iam_actor_identifiers
             WHERE actor_id = :actor_id
             LIMIT 1'
        );

        $stmt->execute([
            'actor_id' => $actorId
        ]);

        $row = $stmt->fetch();

        $this->assertIsArray($row);

        $this->assertSame('EMAIL', $row['identifier_type']);

        $this->assertNotEmpty($row['cipher']);
        $this->assertNotEmpty($row['iv']);
        $this->assertNotEmpty($row['tag']);

        $this->assertNotEmpty($row['key_id']);

        $this->assertSame(
            'aes-256-gcm',
            $row['algorithm']
        );

        /*
        |----------------------------------------------------
        | Duplicate identifier must fail
        |----------------------------------------------------
        */
        $this->expectException(
            \Maatify\Iam\Domain\Exception\Conflict\ActorAlreadyExistsException::class
        );

        $service->execute(
            $tenantId,
            new ProvisionActorDTO(
                actorType: 'customer',
                identifierType: IdentifierTypeEnum::EMAIL,
                identifier: 'test@example.com',
                password: 'password',
                customerMode: 'standard',
                metadata: []
            )
        );
    }
}
