<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Utils;

/**
 * Version Utility - Reusable version comparison logic
 * 
 * Eliminates code duplication for version constraint checking
 * across dependency resolution and module validation.
 */
final class VersionUtil
{
    /**
     * Check if version satisfies constraint
     */
    public static function satisfiesConstraint(string $version, string $constraint): bool
    {
        if ($constraint === '*') {
            return true;
        }

        if (str_starts_with($constraint, '^')) {
            $requiredVersion = substr($constraint, 1);
            return version_compare($version, $requiredVersion, '>=') &&
                   self::isMajorVersionCompatible($version, $requiredVersion);
        }

        if (str_starts_with($constraint, '~')) {
            $requiredVersion = substr($constraint, 1);
            return version_compare($version, $requiredVersion, '>=') &&
                   self::isMinorVersionCompatible($version, $requiredVersion);
        }

        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 2), '>=');
        }

        if (str_starts_with($constraint, '<=')) {
            return version_compare($version, substr($constraint, 2), '<=');
        }

        if (str_starts_with($constraint, '>')) {
            return version_compare($version, substr($constraint, 1), '>');
        }

        if (str_starts_with($constraint, '<')) {
            return version_compare($version, substr($constraint, 1), '<');
        }

        return version_compare($version, $constraint, '=');
    }

    /**
     * Check major version compatibility
     */
    public static function isMajorVersionCompatible(string $version1, string $version2): bool
    {
        $parts1 = explode('.', $version1);
        $parts2 = explode('.', $version2);
        return ($parts1[0] ?? '0') === ($parts2[0] ?? '0');
    }

    /**
     * Check minor version compatibility
     */
    public static function isMinorVersionCompatible(string $version1, string $version2): bool
    {
        $parts1 = explode('.', $version1);
        $parts2 = explode('.', $version2);
        return ($parts1[0] ?? '0') === ($parts2[0] ?? '0') && 
               ($parts1[1] ?? '0') === ($parts2[1] ?? '0');
    }

    /**
     * Parse version into components
     */
    public static function parseVersion(string $version): array
    {
        $parts = explode('.', $version);
        return [
            'major' => (int)($parts[0] ?? 0),
            'minor' => (int)($parts[1] ?? 0),
            'patch' => (int)($parts[2] ?? 0)
        ];
    }

    /**
     * Normalize version string
     */
    public static function normalize(string $version): string
    {
        $parts = self::parseVersion($version);
        return "{$parts['major']}.{$parts['minor']}.{$parts['patch']}";
    }

    /**
     * Compare two versions
     */
    public static function compare(string $version1, string $version2): int
    {
        return version_compare(
            self::normalize($version1),
            self::normalize($version2)
        );
    }
}
