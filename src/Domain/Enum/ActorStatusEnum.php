<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Enum;

enum ActorStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
