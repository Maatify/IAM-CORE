<?php

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Authorization;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\Authorization\AuthorizationMaatifyException;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;

abstract class IamAuthorizationException extends AuthorizationMaatifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            policy: IamErrorPolicy::instance()
        );
    }
}
