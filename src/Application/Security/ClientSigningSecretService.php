<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use Maatify\Iam\Domain\Repository\ClientSigningSecretRepositoryInterface;

final readonly class ClientSigningSecretService
{
    public function __construct(
        private ClientSigningSecretRepositoryInterface $repository,
        private CryptoProvider $cryptoProvider,
        private CryptoContextProviderInterface $contexts
    ) {
    }

    /** @return string[] */
    public function revealSecretsForClient(int $clientId): array
    {
        $records = $this->repository->findActiveByClientId($clientId);

        $crypto = $this->cryptoProvider->context(
            $this->contexts->systemSecret()
        );

        $out = [];

        foreach ($records as $record) {
            $out[] = $crypto->decrypt(
                $record['cipher'],
                $record['key_id'],
                ReversibleCryptoAlgorithmEnum::from($record['algorithm']),
                new ReversibleCryptoMetadataDTO(
                    $record['iv'],
                    $record['tag']
                )
            );
        }

        return $out;
    }
}
