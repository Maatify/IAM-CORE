<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;

abstract class IamSystemException extends SystemMaatifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null
    ) {
        parent::__construct(
            message          : $message,
            code             : $code,
            previous         : $previous,
            errorCodeOverride: $errorCodeOverride,
            policy           : IamErrorPolicy::instance()
        );
    }
}
