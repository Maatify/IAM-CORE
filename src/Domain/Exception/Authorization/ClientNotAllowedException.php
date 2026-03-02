<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authorization;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class ClientNotAllowedException extends IamAuthorizationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::CLIENT_NOT_ALLOWED;
    }
}
