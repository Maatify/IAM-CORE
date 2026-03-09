<?php

declare(strict_types=1);

namespace Maatify\Iam\Infrastructure\Persistence\MySQL;

use RuntimeException;

final class DbRow
{
    /**
     * @param array<string,mixed> $row
     */
    public static function int(array $row, string $key): int
    {
        if (!array_key_exists($key, $row)) {
            throw new RuntimeException("Missing column: $key");
        }

        $value = $row[$key];

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new RuntimeException("Column $key is not numeric");
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function string(array $row, string $key): string
    {
        if (!array_key_exists($key, $row)) {
            throw new RuntimeException("Missing column: $key");
        }

        $value = $row[$key];

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new RuntimeException("Column $key cannot be converted to string");
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function nullableString(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row)) {
            return null;
        }

        $value = $row[$key];

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new RuntimeException("Column $key cannot be converted to string");
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function bool(array $row, string $key): bool
    {
        if (!array_key_exists($key, $row)) {
            throw new RuntimeException("Missing column: $key");
        }

        $value = $row[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }

        throw new RuntimeException("Column $key cannot be converted to bool");
    }
}
