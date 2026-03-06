<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-06 05:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Security\Crypto;

use Maatify\Crypto\Contract\CryptoContextProviderInterface;

class IAMCryptoContextProvider implements CryptoContextProviderInterface
{
    public function identifierEmail(): string
    {
        return CryptoContext::IDENTIFIER_EMAIL_V1;
    }

    public function identifierPhone(): string
    {
        return CryptoContext::IDENTIFIER_PHONE_V1;
    }

    public function notificationEmailRecipient(): string
    {
        return CryptoContext::NOTIFICATION_EMAIL_RECIPIENT_V1;
    }

    public function notificationEmailPayload(): string
    {
        return CryptoContext::NOTIFICATION_EMAIL_PAYLOAD_V1;
    }

    public function totpSeed(): string
    {
        return CryptoContext::TOTP_SEED_V1;
    }

    public function systemSecret(): string
    {
        return CryptoContext::SYSTEM_SECRET_V1;
    }

    public function emailQueueRecipient(): string
    {
        return CryptoContext::EMAIL_QUEUE_RECIPIENT_V1;
    }

    public function emailQueuePayload(): string
    {
        return CryptoContext::EMAIL_QUEUE_PAYLOAD_V1;
    }

    public function abuseProtection(): string
    {
        return CryptoContext::ABUSE_PROTECTION_SIGNAL_V1;
    }
}
