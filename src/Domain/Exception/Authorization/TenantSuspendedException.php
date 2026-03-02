<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authorization;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class TenantSuspendedException extends IamAuthorizationException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::TENANT_SUSPENDED;
    }
}
