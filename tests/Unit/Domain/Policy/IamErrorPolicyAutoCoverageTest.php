<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Policy;

use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;
use PHPUnit\Framework\TestCase;

final class IamErrorPolicyAutoCoverageTest extends TestCase
{
    public function test_all_enum_cases_are_mapped_in_policy(): void
    {
        $policy = IamErrorPolicy::instance();
        $mapping = $policy->mapping();

        // Flatten allowed codes
        $allowed = [];

        foreach ($mapping as $codes) {
            foreach ($codes as $code) {
                $allowed[] = $code;
            }
        }

        $allowed = array_unique($allowed);

        foreach (IamErrorCodeEnum::cases() as $case) {
            $this->assertContains(
                $case->getValue(),
                $allowed,
                sprintf(
                    'ErrorCode "%s" is not mapped in IamErrorPolicy.',
                    $case->getValue()
                )
            );
        }
    }
}