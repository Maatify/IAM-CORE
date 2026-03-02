<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\NotFound;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Enum\IamErrorCodeEnum;

final class TenantNotFoundException extends IamNotFoundException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return IamErrorCodeEnum::TENANT_NOT_FOUND;
    }
}
