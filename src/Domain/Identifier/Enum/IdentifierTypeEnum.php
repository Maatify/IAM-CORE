<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Identifier\Enum;

enum IdentifierTypeEnum: string
{
    case EMAIL = 'EMAIL';
    case PHONE = 'PHONE';
}
