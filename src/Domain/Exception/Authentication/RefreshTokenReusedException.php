<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authentication;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class RefreshTokenReusedException extends IamAuthenticationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::REFRESH_TOKEN_REUSED;
    }
}
