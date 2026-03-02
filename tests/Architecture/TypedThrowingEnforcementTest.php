<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Architectural Enforcement:
 * - Forbid throwing base IAM exception classes.
 * - Require throwing only concrete typed exceptions.
 *
 * STRICT:
 * - Token-based (no regex)
 * - No framework coupling
 * - Deterministic output
 */
final class TypedThrowingEnforcementTest extends TestCase
{
    /**
     * Base exceptions that MUST NOT be thrown directly.
     * Add/remove only by conscious architectural decision.
     *
     * @var array<string, true>
     */
    private const FORBIDDEN_THROW_SHORT_NAMES = [
        'IamSystemException'         => true,
        'IamAuthenticationException' => true,
        'IamAuthorizationException'  => true,
        'IamNotFoundException'       => true,
        'IamConflictException'       => true,
    ];

    /**
     * @var array<string, true>
     */
    private const FORBIDDEN_THROW_FQCN = [
        // If your actual FQCN differs, update these values ONLY (no other changes needed).
        'Maatify\\Iam\\Domain\\Exception\\IamSystemException'         => true,
        'Maatify\\Iam\\Domain\\Exception\\IamAuthenticationException' => true,
        'Maatify\\Iam\\Domain\\Exception\\IamAuthorizationException'  => true,
        'Maatify\\Iam\\Domain\\Exception\\IamNotFoundException'       => true,
        'Maatify\\Iam\\Domain\\Exception\\IamConflictException'       => true,
    ];

    public function test_it_forbids_throwing_base_iam_exceptions(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $srcDir = $projectRoot . DIRECTORY_SEPARATOR . 'src';

        $violations = [];

        foreach ($this->phpFilesUnder($srcDir) as $file) {
            $code = file_get_contents($file->getPathname());
            if ($code === false) {
                $this->fail('Unable to read file: ' . $file->getPathname());
            }

            foreach ($this->findForbiddenThrows($code) as $className) {
                $violations[] = $file->getPathname() . ' => throw new ' . $className;
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Forbidden base IAM exception throwing detected:\n- " . implode("\n- ", $violations)
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function phpFilesUnder(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = [];

        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }

    /**
     * Token-based detection for patterns:
     *   throw new <ClassName>(...)
     *
     * Returns the class names found that are forbidden.
     *
     * @return list<string>
     */
    private function findForbiddenThrows(string $code): array
    {
        $tokens = token_get_all($code);
        $found  = [];

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];

            if (!is_array($t) || $t[0] !== T_THROW) {
                continue;
            }

            $i = $this->skipIgnorables($tokens, $i + 1);
            if ($i >= $count) {
                break;
            }

            $t = $tokens[$i];
            if (!is_array($t) || $t[0] !== T_NEW) {
                continue;
            }

            $i = $this->skipIgnorables($tokens, $i + 1);
            if ($i >= $count) {
                break;
            }

            $className = $this->readClassName($tokens, $i);
            if ($className === null) {
                continue;
            }

            $short = $this->shortName($className);

            if (isset(self::FORBIDDEN_THROW_SHORT_NAMES[$short]) || isset(self::FORBIDDEN_THROW_FQCN[ltrim($className, '\\')])) {
                $found[] = $className;
            }
        }

        return $found;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function skipIgnorables(array $tokens, int $i): int
    {
        $count = count($tokens);

        while ($i < $count) {
            $t = $tokens[$i];

            if (is_array($t)) {
                if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                    $i++;
                    continue;
                }
            }

            return $i;
        }

        return $i;
    }

    /**
     * Reads a class name starting at token index $i after "new".
     * Supports:
     * - Fully qualified: \Vendor\Pkg\Class
     * - Qualified: Vendor\Pkg\Class
     * - Short: Class
     *
     * @param array<int, mixed> $tokens
     */
    private function readClassName(array $tokens, int $i): ?string
    {
        $count = count($tokens);
        $name  = '';

        // optional leading backslash
        if ($i < $count && $tokens[$i] === '\\') {
            $name .= '\\';
            $i++;
        }

        while ($i < $count) {
            $t = $tokens[$i];

            if (is_array($t) && $t[0] === T_STRING) {
                $name .= $t[1];
                $i++;
                continue;
            }

            if ($t === '\\' || (is_array($t) && $t[0] === T_NS_SEPARATOR)) {
                $name .= '\\';
                $i++;
                continue;
            }

            break;
        }

        $name = trim($name);

        if ($name === '' || $name === '\\') {
            return null;
        }

        return $name;
    }

    private function shortName(string $className): string
    {
        $className = ltrim($className, '\\');
        $pos = strrpos($className, '\\');

        if ($pos === false) {
            return $className;
        }

        return substr($className, $pos + 1);
    }
}
