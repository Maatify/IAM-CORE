<?php

declare(strict_types=1);

namespace Maatify\Iam\Application\Security;

final class JsonCanonicalizer
{
    public function canonicalize(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $decoded = json_decode($body, true, 64);

        if (json_last_error() === JSON_ERROR_DEPTH) {
            throw new \InvalidArgumentException('JSON payload exceeds maximum allowed depth');
        }

        if (!is_array($decoded)) {
            return $body;
        }

        $this->ksortRecursive($decoded);

        return json_encode(
            $decoded,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
            | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    private function ksortRecursive(array &$array): void
    {
        if ($this->isAssoc($array)) {
            ksort($array);
        }

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    /**
     * @param array<string,mixed> $array
     */
    private function isAssoc(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
