<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authorization;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class ActorSuspendedException extends IamAuthorizationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::ACTOR_SUSPENDED;
    }
}
