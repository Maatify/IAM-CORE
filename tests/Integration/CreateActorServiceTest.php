<?php

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
use Maatify\Iam\Application\Service\CreateActorService;
use Maatify\Iam\Domain\DTO\CreateActorCommandDTO;
use Maatify\Iam\Domain\Identifier\Canonicalizer\EmailCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;
use Maatify\Iam\Domain\Identifier\Canonicalizer\PhoneCanonicalizer;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;
use Maatify\Iam\Domain\Security\Lookup\LookupHmac;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorIdentifierRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoActorRepository;
use Maatify\Iam\Infrastructure\Persistence\MySQL\PdoTransactionManager;
use Tests\Support\Crypto\TestCryptoContextProvider;
use Tests\Support\Security\EnvLookupSecretProvider;
use Tests\Support\TestDatabaseManager;

final class CreateActorServiceTest extends IntegrationTestCase
{
    public function test_creates_actor_and_identifier_encrypted_with_unique_lookup(): void
    {
        $pdo = TestDatabaseManager::connection();

        // Seed tenant (required by FK)
        $pdo->exec("
            INSERT INTO iam_tenants (`key`, name, status, metadata_json, primary_identifier_type)
            VALUES ('t1', 'Tenant 1', 'ACTIVE', NULL, 'EMAIL')
        ");
        $tenantId = (int)$pdo->lastInsertId();

        // Build canonicalizer + lookup hmac
        $canonicalizer = new IdentifierCanonicalizer(
            new EmailCanonicalizer(),
            new PhoneCanonicalizer()
        );

        $lookupHmac = new LookupHmac(
            new EnvLookupSecretProvider()
        );

        // Build CryptoProvider (HKDF context-based)
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
                status: $id === $activeKeyId ? KeyStatusEnum::ACTIVE : KeyStatusEnum::INACTIVE,
                createdAt: new \DateTimeImmutable('2026-03-04 00:00:00')
            );
        }

        $keyProvider = new InMemoryKeyProvider($keys);
        $keyRotation = new KeyRotationService($keyProvider, new StrictSingleActiveKeyPolicy());

        $contextFactory = new CryptoContextFactory(
            $keyRotation,
            new HKDFService(),
            $registry
        );

        $directFactory = new CryptoDirectFactory(
            $keyRotation,
            $registry
        );

        $crypto = new CryptoProvider($contextFactory, $directFactory);

        $service = new CreateActorService(
            new PdoActorRepository($pdo),
            new PdoActorIdentifierRepository($pdo),
            $canonicalizer,
            $lookupHmac,
            $crypto,
            new TestCryptoContextProvider(),
            new PdoTransactionManager($pdo)
        );

        $actorId = $service->execute(new CreateActorCommandDTO(
            tenantId: $tenantId,
            actorType: 'customer',
            identifierType: IdentifierTypeEnum::EMAIL,
            rawIdentifier: '  TEST@Example.com ',
        ));

        $this->assertGreaterThan(0, $actorId);

        // Ensure identifier row exists with cipher/iv/tag/key_id/algorithm populated
        $stmt = $pdo->prepare('SELECT * FROM iam_actor_identifiers WHERE actor_id = :actor_id LIMIT 1');
        $stmt->execute(['actor_id' => $actorId]);
        $row = $stmt->fetch();

        $this->assertIsArray($row);

        $this->assertSame('EMAIL', $row['identifier_type']);
        $this->assertNotEmpty($row['cipher']);
        $this->assertNotEmpty($row['iv']);
        $this->assertNotEmpty($row['tag']);
        $this->assertNotEmpty($row['key_id']);
        $this->assertSame('aes-256-gcm', $row['algorithm']);

        // Try same identifier again -> must fail unique lookup (service layer)
        $this->expectException(\Maatify\Iam\Domain\Exception\Conflict\ActorAlreadyExistsException::class);

        $service->execute(new CreateActorCommandDTO(
            tenantId: $tenantId,
            actorType: 'customer',
            identifierType: IdentifierTypeEnum::EMAIL,
            rawIdentifier: 'test@example.com'
        ));
    }
}
