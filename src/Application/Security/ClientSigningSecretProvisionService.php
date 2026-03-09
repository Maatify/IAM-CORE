<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-08 05:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Iam\Domain\Exception\NotFound\ClientNotFoundException;
use Maatify\Iam\Domain\Repository\ClientRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientSigningSecretRepositoryInterface;

final readonly class ClientSigningSecretProvisionService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ClientSigningSecretRepositoryInterface $repository,
        private CryptoProvider $cryptoProvider,
        private CryptoContextProviderInterface $contexts
    ) {
    }

    public function createForClient(int $clientId): string
    {

        $client = $this->clientRepository->findById($clientId);

        if (!$client) {
            throw new ClientNotFoundException("Client not found: {$clientId}");
        }

        $rawSecret = bin2hex(random_bytes(32));

        $crypto = $this->cryptoProvider->context(
            $this->contexts->systemSecret()
        );

        $encrypted = $crypto->encrypt($rawSecret);

        /** @var array{
         *     result:\Maatify\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO,
         *     key_id:string,
         *     algorithm:\Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum
         * } $encrypted
         */
        $result = $encrypted['result'];

        $this->repository->storeSecret(
            clientId  : $clientId,
            cipher    : $result->cipher,
            iv        : $result->iv ?? '',
            tag       : $result->tag ?? '',
            keyId     : $encrypted['key_id'],
            algorithm : $encrypted['algorithm']->value
        );

        // ⚠️ DO NOT log this value
        return $rawSecret;
    }
}
