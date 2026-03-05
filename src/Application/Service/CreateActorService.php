<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-03 22:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Service;

use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Iam\Application\Contract\TransactionManagerInterface;
use Maatify\Iam\Domain\DTO\CreateActorCommandDTO;
use Maatify\Iam\Domain\Exception\Conflict\ActorAlreadyExistsException;
use Maatify\Iam\Domain\Exception\System\DatabaseWriteFailedException;
use Maatify\Iam\Domain\Identifier\Canonicalizer\IdentifierCanonicalizer;
use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;
use Maatify\Iam\Domain\Repository\ActorIdentifierRepositoryInterface;
use Maatify\Iam\Domain\Repository\ActorRepositoryInterface;
use Maatify\Iam\Domain\Security\Lookup\LookupHmacInterface;

final readonly class CreateActorService
{
    public function __construct(
        private ActorRepositoryInterface $actorRepo,
        private ActorIdentifierRepositoryInterface $identifierRepo,
        private IdentifierCanonicalizer $canonicalizer,
        private LookupHmacInterface $lookupHmac,

        // Crypto integration (required by project)
        private CryptoProvider $crypto,
        private TransactionManagerInterface $transaction,
    ) {
    }

    public function execute(CreateActorCommandDTO $command): int
    {
        $canonical = $this->canonicalizer->canonicalize(
            $command->identifierType,
            $command->rawIdentifier
        );

        $lookupHash = $this->lookupHmac->hash($canonical);

        if ($this->identifierRepo->existsByLookup(
            $command->tenantId,
            $command->actorType,
            $command->identifierType->value,
            $lookupHash
        )) {
            throw new ActorAlreadyExistsException();
        }

        return $this->transaction->transactional(function () use ($command, $lookupHash, $canonical) {

            $service = $this->crypto->context('IDENTIFIER_ENC');
            $encrypted = $service->encrypt($canonical);

            $cipher = $encrypted['result']->cipher;
            $iv     = $encrypted['result']->iv ?? '';
            $tag    = $encrypted['result']->tag ?? '';
            $keyId  = (string)$encrypted['key_id'];
            $alg    = (string)$encrypted['algorithm']->value;

            try {
                $actorId = $this->actorRepo->insert(
                    $command->tenantId,
                    $command->actorType,
                    $command->status->value,
                );

                $this->identifierRepo->insert(
                    $actorId,
                    $command->tenantId,
                    $command->actorType,
                    $command->identifierType->value,
                    $lookupHash,
                    $cipher,
                    $iv,
                    $tag,
                    $keyId,
                    $alg,
                    false
                );

                return $actorId;

            } catch (\Throwable $e) {
                // 🔴 Map DB Unique Constraint → Domain Conflict
                if ($this->isDuplicateKeyException($e)) {
                    throw new ActorAlreadyExistsException(previous: $e);
                }
                throw new DatabaseWriteFailedException(previous: $e);
            }
        });
    }

    private function isDuplicateKeyException(\Throwable $e): bool
    {
        if (!$e instanceof \PDOException) {
            return false;
        }

        // SQLSTATE 23000 = Integrity constraint violation
        if (($e->getCode() === '23000') || ((string)$e->getCode() === '23000')) {
            return true;
        }

        // MySQL duplicate entry error code
        $errorInfo = $e->errorInfo ?? null;

        if (is_array($errorInfo) && isset($errorInfo[1])) {
            return (int)$errorInfo[1] === 1062;
        }

        return false;
    }
}
