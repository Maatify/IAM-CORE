<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Policy;

use LogicException;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;
use PHPUnit\Framework\TestCase;

final class IamErrorPolicyTest extends TestCase
{
    private IamErrorPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = IamErrorPolicy::instance();
    }

    // ---------------------------------------------------------
    // 1️⃣ VALID CATEGORY MAPPING
    // ---------------------------------------------------------

    public function test_authentication_codes_are_valid(): void
    {
        $this->policy->validate(
            IamErrorCodeEnum::INVALID_CREDENTIALS,
            ErrorCategoryEnum::AUTHENTICATION
        );

        $this->policy->validate(
            IamErrorCodeEnum::REFRESH_TOKEN_REUSED,
            ErrorCategoryEnum::AUTHENTICATION
        );

        $this->assertTrue(true);
    }

    public function test_authorization_codes_are_valid(): void
    {
        $this->policy->validate(
            IamErrorCodeEnum::ACTOR_SUSPENDED,
            ErrorCategoryEnum::AUTHORIZATION
        );

        $this->assertTrue(true);
    }

    public function test_system_codes_are_valid(): void
    {
        $this->policy->validate(
            IamErrorCodeEnum::CRYPTO_FAILURE,
            ErrorCategoryEnum::SYSTEM
        );

        $this->assertTrue(true);
    }

    // ---------------------------------------------------------
    // 2️⃣ INVALID CATEGORY MAPPING
    // ---------------------------------------------------------

    public function test_invalid_category_mapping_throws(): void
    {
        $this->expectException(LogicException::class);

        $this->policy->validate(
            IamErrorCodeEnum::INVALID_CREDENTIALS,
            ErrorCategoryEnum::SYSTEM // wrong category
        );
    }

    // ---------------------------------------------------------
    // 3️⃣ CATEGORY NOT CONFIGURED IN POLICY
    // ---------------------------------------------------------

    public function test_unconfigured_category_is_allowed(): void
    {
        // SECURITY category not explicitly configured in IAM policy
        $this->policy->validate(
            IamErrorCodeEnum::INVALID_TOKEN,
            ErrorCategoryEnum::SECURITY
        );

        $this->assertTrue(true);
    }

    // ---------------------------------------------------------
    // 4️⃣ SEVERITY DELEGATION
    // ---------------------------------------------------------

    public function test_severity_delegates_to_default_policy(): void
    {
        $severity = $this->policy->severity(
            ErrorCategoryEnum::SYSTEM
        );

        $this->assertGreaterThan(0, $severity);
    }
}
