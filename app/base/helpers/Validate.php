<?php
declare(strict_types=1);
/**
 * Validation Helper
 *
 * Centralized validation logic for security and data integrity
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Validate
{
    /**
     * Sanitize input (remove special characters)
     *
     * @param mixed $value Value to sanitize
     * @param int $filter Filter type
     * @return mixed
     */
    public static function sanitize($value, int $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    {
        if (is_array($value)) {
            return array_map(fn($item) => self::sanitize($item, $filter), $value);
        }

        return filter_var($value, $filter);
    }

    /**
     * Check if path is within allowed directory (security)
     *
     * @param string $path Path to check
     * @param string $allowedBase Allowed base directory
     * @return bool
     */
    public static function pathIsSecure(string $path, string $allowedBase): bool
    {
        $realPath = realpath($path);
        $realBase = realpath($allowedBase);

        if ($realPath === false || $realBase === false) {
            return false;
        }

        return strpos($realPath, $realBase) === 0;
    }
}
