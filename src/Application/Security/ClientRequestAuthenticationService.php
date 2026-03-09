<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

use DateInterval;
use DateTimeImmutable;
use Maatify\Exceptions\Exception\Authentication\UnauthorizedMaatifyException;
use Maatify\Iam\Application\Security\DTO\SignedClientRequestDTO;
use Maatify\Iam\Domain\Entity\Client;
use Maatify\Iam\Domain\Repository\ClientNonceRepositoryInterface;
use Maatify\Iam\Domain\Repository\ClientRepositoryInterface;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class ClientRequestAuthenticationService
{
    private const MAX_CLOCK_SKEW_SECONDS = 60;

    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ClientNonceRepositoryInterface $nonceRepository,
        private ClientSigningSecretService $signingSecretService,
        private ClientRequestSignatureService $signatureService,
        private LoggerInterface $logger,
        private ClockInterface $clock
    ) {
    }

    public function authenticate(SignedClientRequestDTO $dto): Client
    {

        if (
            $dto->clientKey === '' ||
            $dto->timestamp === '' ||
            $dto->nonce === '' ||
            $dto->signature === ''
        ) {
            throw new UnauthorizedMaatifyException();
        }

        $client = $this->clientRepository->findByClientKey($dto->clientKey);

        if (!$client || $client->status !== 'ACTIVE') {
            throw new UnauthorizedMaatifyException();
        }

        $timestamp = new DateTimeImmutable('@' . $dto->timestamp);
        $delta = abs($this->clock->now()->getTimestamp() - $timestamp->getTimestamp());

        if ($delta > self::MAX_CLOCK_SKEW_SECONDS) {
            $this->logger->warning(
                'Client request rejected due to clock skew',
                [
                    'client_key' => $dto->clientKey,
                    'request_timestamp' => $timestamp->getTimestamp(),
                    'server_time' => (new DateTimeImmutable('now'))->getTimestamp(),
                    'delta' => $delta,
                    'max_allowed' => self::MAX_CLOCK_SKEW_SECONDS
                ]
            );
            throw new UnauthorizedMaatifyException();
        }

        $payload = $this->signatureService->buildPayload(
            $dto->clientKey,
            $dto->method,
            $dto->host,
            $dto->path,
            $dto->timestamp,
            $dto->nonce,
            $dto->body
        );

        $secrets = $this->signingSecretService->revealSecretsForClient($client->id);

        $valid = false;

        foreach ($secrets as $secret) {
            $expected = $this->signatureService->compute($payload, $secret);

            if ($this->signatureService->verify($dto->signature, $expected)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $this->logger->error(
                'Client signature verification failed',
                [
                    'client' => $dto->clientKey,
                    'path' => $dto->path
                ]
            );
            throw new UnauthorizedMaatifyException();
        }

        $stored = $this->nonceRepository->storeIfUnused(
            $client->id,
            $dto->nonce,
            $timestamp->add(new DateInterval('PT2M'))
        );

        if (!$stored) {
            throw new UnauthorizedMaatifyException();
        }

        return $client;
    }
}
