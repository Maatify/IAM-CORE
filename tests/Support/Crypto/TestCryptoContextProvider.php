<?php

declare(strict_types=1);

namespace Tests\Support\Crypto;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;

final readonly class TestCryptoContextProvider implements CryptoContextProviderInterface
{
    public function identifierEmail(): string
    {
        return 'identifier:email:v1';
    }

    public function identifierPhone(): string
    {
        return 'identifier:phone:v1';
    }

    public function notificationEmailRecipient(): string
    {
        return 'notification:email:recipient:v1';
    }

    public function notificationEmailPayload(): string
    {
        return 'notification:email:payload:v1';
    }

    public function emailQueueRecipient(): string
    {
        return 'email:queue:recipient:v1';
    }

    public function emailQueuePayload(): string
    {
        return 'email:queue:payload:v1';
    }

    public function totpSeed(): string
    {
        return 'totp:seed:v1';
    }

    public function systemSecret(): string
    {
        return 'system:secret:v1';
    }

    public function abuseProtection(): string
    {
        return 'abuse:protection:signal:v1';
    }
}
