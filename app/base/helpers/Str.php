<?php
declare(strict_types=1);
/**
 * String Helper
 *
 * String manipulation utilities
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Str
{
    /**
     * Convert string to camelCase
     *
     * @param string $value String to convert
     * @return string
     */
    public static function camel(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        return lcfirst($value);
    }

    /**
     * Generate URL-friendly slug
     *
     * @param string $value String to convert
     * @return string
     */
    public static function slug(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * Limit string to specified length
     *
     * @param string $value String to limit
     * @param int $limit Maximum length
     * @param string $end String to append if truncated
     * @return string
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit) . $end;
    }

    /**
     * Generate random string
     *
     * @param int $length Length of random string
     * @return string
     */
    public static function random(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result;
    }
}
