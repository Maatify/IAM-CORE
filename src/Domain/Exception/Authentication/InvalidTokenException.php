<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authentication;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class InvalidTokenException extends IamAuthenticationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::INVALID_TOKEN;
    }
}
