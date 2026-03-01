<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-01 23:24
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Domain\Exception\Infrastructure;

use Maatify\Exceptions\Exception\System\SystemMaatifyException;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Iam\Domain\Policy\IamErrorPolicy;

abstract class IamInfrastructureException extends SystemMaatifyException
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
