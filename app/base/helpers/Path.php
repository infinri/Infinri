<?php
declare(strict_types=1);
/**
 * Path Helper
 *
 * Centralized path utilities for filesystem operations
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Path
{
    /**
     * Join path segments
     *
     * @param string ...$parts Path parts to join
     * @return string
     */
    public static function join(string ...$parts): string
    {
        $path = implode('/', $parts);
        return self::normalize($path);
    }

    /**
     * Normalize path (remove double slashes, resolve relative paths)
     *
     * @param string $path Path to normalize
     * @return string
     */
    public static function normalize(string $path): string
    {
        // Security: Reject null bytes and path traversal
        if (strpos($path, "\0") !== false || strpos($path, '..') !== false) {
            throw new \InvalidArgumentException('Invalid path');
        }

        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove multiple consecutive slashes
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /**
     * Get module path
     *
     * @param string $module Module name
     * @param string $file Optional file within module
     * @return string
     */
    public static function module(string $module, string $file = ''): string
    {
        // Security: Validate module name (alphanumeric, underscore, hyphen only)
        if (! preg_match('/^[a-z0-9_-]+$/', $module)) {
            throw new \InvalidArgumentException('Invalid module name');
        }

        // Security: Validate file path if provided
        if ($file !== '' && (strpos($file, '..') !== false || strpos($file, "\0") !== false)) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        $path = dirname(__DIR__, 2) . '/modules/' . $module;

        if ($file) {
            $path .= '/' . $file;
        }

        return self::normalize($path);
    }

    /**
     * Scan directory and return items (excluding . and ..)
     *
     * @param string $directory Directory to scan
     * @return array List of items in directory
     */
    public static function scanDir(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $items = scandir($directory);
        return array_filter($items, fn($item) => $item !== '.' && $item !== '..');
    }
}
