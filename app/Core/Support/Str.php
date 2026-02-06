<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Support;

/**
 * String Helper
 *
 * Centralized string manipulation utilities.
 * Single source of truth for common string operations.
 */
final class Str
{
    /**
     * Normalize a URI path (ensure leading slash, optional trailing slash removal)
     */
    public static function normalizeUri(string $uri, bool $keepTrailing = false): string
    {
        $uri = '/' . trim($uri, '/');

        if ($uri === '/') {
            return '/';
        }

        return $keepTrailing ? $uri : rtrim($uri, '/');
    }

    /**
     * Combine URI segments safely
     */
    public static function joinUri(string ...$segments): string
    {
        $path = implode('/', array_map(fn ($s) => trim($s, '/'), $segments));

        return self::normalizeUri($path);
    }

    /**
     * Convert header key to HTTP format (Title-Case)
     */
    public static function headerToHttpFormat(string $key): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower($key))));
    }

    /**
     * Extract header name from SERVER variable key
     */
    public static function serverKeyToHeader(string $key): string
    {
        if (str_starts_with($key, 'HTTP_')) {
            $key = substr($key, 5);
        }

        return str_replace('_', '-', $key);
    }

    /**
     * Check if string contains any of the given needles
     */
    public static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Safely convert value to string
     */
    public static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_null($value)) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Parse a string value to boolean
     */
    public static function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '1', 'yes', 'on' => true,
                default => false,
            };
        }

        return (bool) $value;
    }

    /**
     * Convert string to StudlyCase (PascalCase)
     */
    public static function studly(string $value): string
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $value));

        return implode('', array_map('ucfirst', $words));
    }

    /**
     * Convert string to camelCase
     */
    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    /**
     * Convert string to snake_case
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));

        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
    }

    /**
     * Convert string to kebab-case
     */
    public static function kebab(string $value): string
    {
        return self::snake($value, '-');
    }

    /**
     * Get the class basename (without namespace)
     */
    public static function classBasename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Generate a URL-friendly slug
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = preg_replace('/[^\pL\d]+/u', $separator, $value);
        $value = preg_replace('/[' . preg_quote($separator) . ']+/u', $separator, $value);

        return strtolower(trim($value, $separator));
    }

    /**
     * Limit string to a given length
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }

    /**
     * Generate a random string
     */
    public static function random(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Check if string starts with any of the given values
     */
    public static function startsWithAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string ends with any of the given values
     */
    public static function endsWithAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
