<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Conflict;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class ActorAlreadyExistsException extends IamConflictException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::ACTOR_ALREADY_EXISTS;
    }
}
