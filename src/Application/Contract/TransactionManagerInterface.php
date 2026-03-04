<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-04 03:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Application\Contract;

interface TransactionManagerInterface
{
    /**
     * Execute a callable inside a DB transaction.
     *
     * @template T
     * @param   callable():T  $callback
     *
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
