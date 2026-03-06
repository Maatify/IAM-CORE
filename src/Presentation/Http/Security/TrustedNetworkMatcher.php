<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/IAM-CORE
 * @Project     maatify:IAM-CORE
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-03-05 01:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/IAM-CORE view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Iam\Presentation\Http\Security;

final readonly class TrustedNetworkMatcher implements TrustedNetworkMatcherInterface
{
    /**
     * @param   list<string>  $rules  Example: ["127.0.0.1", "10.0.0.0/8", "192.168.1.0/24", "::1"]
     */
    public function __construct(
        private array $rules
    ) {
    }

    public function isTrusted(string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        foreach ($this->rules as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }

            // Exact match
            if (strpos($rule, '/') === false) {
                if ($this->ipEquals($ip, $rule)) {
                    return true;
                }
                continue;
            }

            // CIDR match
            if ($this->ipInCidr($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function ipEquals(string $a, string $b): bool
    {
        $pa = @inet_pton($a);
        $pb = @inet_pton($b);

        if ($pa === false || $pb === false) {
            return false;
        }

        return hash_equals($pa, $pb);
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);

        [$subnet, $prefix] = array_pad(explode('/', $cidr, 2), 2, '');
        $subnet = trim($subnet);
        $prefix = trim($prefix);

        if ($subnet === '' || $prefix === '' || ! ctype_digit($prefix)) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subBin = @inet_pton($subnet);

        if ($ipBin === false || $subBin === false) {
            return false;
        }

        // IPv4 length=4, IPv6 length=16
        if (strlen($ipBin) !== strlen($subBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        $p = (int)$prefix;

        if ($p < 0 || $p > $maxBits) {
            return false;
        }

        $bytes = intdiv($p, 8);
        $bits = $p % 8;

        // Compare full bytes
        if ($bytes > 0) {
            if (substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
                return false;
            }
        }

        // Compare remaining bits
        if ($bits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $bits)) & 0xFF);

        $ipByte = $ipBin[$bytes] ?? "\x00";
        $subByte = $subBin[$bytes] ?? "\x00";

        return (($ipByte & $mask) === ($subByte & $mask));
    }
}
