<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Policy;

use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;
use PHPUnit\Framework\TestCase;

final class IamErrorPolicyIntegrityTest extends TestCase
{
    public function test_no_duplicate_codes_across_categories(): void
    {
        $policy = IamErrorPolicy::instance();
        $mapping = $policy->mapping();

        $all = [];

        foreach ($mapping as $category => $codes) {
            foreach ($codes as $code) {
                $this->assertFalse(
                    in_array($code, $all, true),
                    sprintf(
                        'ErrorCode "%s" is duplicated across categories (detected in "%s").',
                        $code,
                        $category
                    )
                );

                $all[] = $code;
            }
        }

        $this->assertTrue(true);
    }

    public function test_no_duplicate_codes_inside_single_category(): void
    {
        $policy = IamErrorPolicy::instance();
        $mapping = $policy->mapping();

        foreach ($mapping as $category => $codes) {
            $this->assertSame(
                count($codes),
                count(array_unique($codes)),
                sprintf(
                    'Duplicate ErrorCode detected inside category "%s".',
                    $category
                )
            );
        }
    }

    public function test_all_policy_codes_exist_in_enum(): void
    {
        $policy = IamErrorPolicy::instance();
        $mapping = $policy->mapping();

        $enumValues = array_map(
            fn($case) => $case->getValue(),
            IamErrorCodeEnum::cases()
        );

        foreach ($mapping as $category => $codes) {
            foreach ($codes as $code) {
                $this->assertContains(
                    $code,
                    $enumValues,
                    sprintf(
                        'Policy references unknown ErrorCode "%s" in category "%s".',
                        $code,
                        $category
                    )
                );
            }
        }
    }

    public function test_no_empty_categories(): void
    {
        $policy = IamErrorPolicy::instance();
        $mapping = $policy->mapping();

        foreach ($mapping as $category => $codes) {
            $this->assertNotEmpty(
                $codes,
                sprintf(
                    'Category "%s" is empty. If intentional, document explicitly.',
                    $category
                )
            );
        }
    }
}