<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authentication;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class TokenExpiredException extends IamAuthenticationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::TOKEN_EXPIRED;
    }
}
