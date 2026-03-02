<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-02 03:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Error;

use Throwable;
use Maatify\Exceptions\Application\Error\DefaultThrowableToError;
use Maatify\Exceptions\Application\Error\ErrorContext;
use Maatify\Exceptions\Application\Error\ErrorResponseModel;
use Maatify\Exceptions\Application\Error\ErrorSerializer;
use Maatify\Exceptions\Application\Format\ProblemDetailsFormatter;

/**
 * Thin deterministic adapter over maatify/exceptions error serialization layer.
 *
 * STRICT:
 * - No logging
 * - No context mutation
 * - No catch blocks
 * - No framework coupling
 */
final class IamErrorResponder
{
    private ErrorSerializer $serializer;

    public function __construct()
    {
        $mapper    = new DefaultThrowableToError();
        $formatter = new ProblemDetailsFormatter();

        $this->serializer = new ErrorSerializer($mapper, $formatter);
    }

    public function respond(Throwable $throwable, ErrorContext $context): ErrorResponseModel
    {
        return $this->serializer->serialize($throwable, $context);
    }
}
