<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 02:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Service;

use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Iam\Domain\DTO\ProvisionActorDTO;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;
use Maatify\Iam\Domain\Repository\ActorIdentifierRepositoryInterface;
use Maatify\Iam\Domain\Security\Crypto\IdentifierCryptoContextResolver;
use Maatify\Iam\Domain\Security\Lookup\LookupHmacInterface;

final readonly class IdentifierService
{
    public function __construct(
        private ActorIdentifierRepositoryInterface $identifierRepo,
        private IdentifierCanonicalizer $canonicalizer,
        private LookupHmacInterface $lookupHmac,
        private CryptoProvider $cryptoProvider,
        private IdentifierCryptoContextResolver $contextResolver
    ) {
    }

    public function create(
        int $actorId,
        int $tenantId,
        ProvisionActorDTO $dto
    ): void {

        $canonical = $this->canonicalizer->canonicalize(
            $dto->identifierType,
            $dto->identifier
        );

        $lookupHash = $this->lookupHmac->hash($canonical);

        $context = $this->contextResolver->resolve($dto->identifierType);

        $encrypted = $this->cryptoProvider
            ->context($context)
            ->encrypt($canonical);

        $cipher = $encrypted['result']->cipher;
        $iv     = $encrypted['result']->iv ?? '';
        $tag    = $encrypted['result']->tag ?? '';
        $keyId  = (string)$encrypted['key_id'];
        $alg    = (string)$encrypted['algorithm']->value;

        $this->identifierRepo->insert(
            $actorId,
            $tenantId,
            $dto->actorType,
            $dto->identifierType->value,
            $lookupHash,
            $cipher,
            $iv,
            $tag,
            $keyId,
            $alg,
            false
        );
    }
}
