<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-07 03:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Support;

use PDOException;
use Throwable;

use Maatify\Iam\Domain\Exception\Conflict\ActorAlreadyExistsException;

final class ExceptionMapper
{
    public static function map(Throwable $e): Throwable
    {
        if (self::isDuplicateKey($e)) {
            return new ActorAlreadyExistsException(previous: $e);
        }

        return $e;
    }

    private static function isDuplicateKey(Throwable $e): bool
    {
        if (! $e instanceof PDOException) {
            return false;
        }

        if ($e->getCode() === '23000' || (string)$e->getCode() === '23000') {
            return true;
        }

        $errorInfo = $e->errorInfo ?? null;

        if (is_array($errorInfo) && isset($errorInfo[1])) {
            return (int)$errorInfo[1] === 1062;
        }

        return false;
    }
}
