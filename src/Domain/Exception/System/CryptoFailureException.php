<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\System;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;
use Maatify\Iam\Domain\Exception\IamSystemException;

final class CryptoFailureException extends IamSystemException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::CRYPTO_FAILURE;
    }
}
