<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 05:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Schemas;

use Maatify\Iam\Domain\Identifier\Enum\IdentifierTypeEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Maatify\Validation\Rules\CredentialInputRule;
use Respect\Validation\Validator as v;

final class ProvisionActorSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [

            'actor_type' => [
                v::in(['CUSTOMER']),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'identifier_type' => [
                v::in(array_column(IdentifierTypeEnum::cases(), 'value')),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'identifier' => [
                v::stringType()->notEmpty()->length(3, 255),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],

            'password' => [
                CredentialInputRule::rule(),
                ValidationErrorCodeEnum::INVALID_PASSWORD
            ],

            'customer_mode' => [
                v::optional(v::stringType()->length(1, 50)),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'metadata' => [
                v::optional(v::arrayType()),
                ValidationErrorCodeEnum::INVALID_VALUE
            ]

        ];
    }
}
