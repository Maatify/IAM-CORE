<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-01 22:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Policy;

use LogicException;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class IamErrorPolicy implements ErrorPolicyInterface
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void {
        $allowed = self::allowedCodesByCategory();

        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        if (!isset($allowed[$categoryId])) {
            return;
        }

        if (!in_array($codeId, $allowed[$categoryId], true)) {
            throw new LogicException(sprintf(
                'Error code "%s" is not allowed for category "%s" in IAM policy.',
                $codeId,
                $categoryId
            ));
        }
    }

    public function severity(ErrorCategoryInterface $category): int
    {
        return DefaultErrorPolicy::default()->severity($category);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'AUTHENTICATION' => [
                IamErrorCodeEnum::INVALID_CREDENTIALS->getValue(),
                IamErrorCodeEnum::INVALID_TOKEN->getValue(),
                IamErrorCodeEnum::TOKEN_EXPIRED->getValue(),
                IamErrorCodeEnum::REFRESH_TOKEN_REUSED->getValue(),
            ],

            'AUTHORIZATION' => [
                IamErrorCodeEnum::ACTOR_SUSPENDED->getValue(),
                IamErrorCodeEnum::TENANT_SUSPENDED->getValue(),
                IamErrorCodeEnum::CLIENT_NOT_ALLOWED->getValue(),
            ],

            'NOT_FOUND' => [
                IamErrorCodeEnum::ACTOR_NOT_FOUND->getValue(),
                IamErrorCodeEnum::TENANT_NOT_FOUND->getValue(),
                IamErrorCodeEnum::SESSION_NOT_FOUND->getValue(),
                IamErrorCodeEnum::CLIENT_NOT_FOUND->getValue(), // ← added
            ],

            'CONFLICT' => [
                IamErrorCodeEnum::ACTOR_ALREADY_EXISTS->getValue(),
                IamErrorCodeEnum::SESSION_ALREADY_REVOKED->getValue(),
                IamErrorCodeEnum::IDEMPOTENCY_CONFLICT->getValue(), // ← added
            ],

            'SYSTEM' => [
                IamErrorCodeEnum::TOKEN_SIGNING_FAILED->getValue(),
                IamErrorCodeEnum::CRYPTO_FAILURE->getValue(),
                IamErrorCodeEnum::SESSION_PERSISTENCE_FAILED->getValue(),
                IamErrorCodeEnum::DATABASE_WRITE_FAILED->getValue(),
            ],
        ];
    }

    /**
     * @internal Used for testing coverage validation.
     *
     * @return array<string, list<string>>
     */
    public function mapping(): array
    {
        return self::allowedCodesByCategory();
    }
}
