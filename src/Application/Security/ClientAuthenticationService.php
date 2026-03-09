<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

use Maatify\Iam\Domain\Repository\ClientRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientSecretRepositoryInterface;
use Maatify\Exceptions\Exception\Authentication\UnauthorizedMaatifyException;
use Maatify\Iam\Domain\Entity\Client;

final readonly class ClientAuthenticationService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ClientSecretRepositoryInterface $secretRepository
    ) {
    }

    public function authenticate(string $clientKey, string $secret): Client
    {
        if ($clientKey === '' || $secret === '') {
            throw new UnauthorizedMaatifyException();
        }

        $client = $this->clientRepository->findByClientKey($clientKey);

        if (! $client) {
            password_verify(
                $secret,
                '$argon2id$v=19$m=65536,t=4,p=1$OG0zbkpKb1J1cVJoRGI4Uw$9uuJRV87hluQeatzjijHIC4Nx9vG1ssQOvgS3lvGr6E'
            );

            throw new UnauthorizedMaatifyException();
        }

        if ($client->status !== 'ACTIVE') {
            throw new UnauthorizedMaatifyException();
        }

        $hashes = $this->secretRepository->findHashesByClientId($client->id);

        $valid = false;

        foreach ($hashes as $hash) {
            if (password_verify($secret, $hash)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            throw new UnauthorizedMaatifyException();
        }

        return $client;
    }
}
