<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Security;

/**
 * Input Sanitizer
 * 
 * Sanitizes user input to prevent XSS and other attacks.
 */
class Sanitizer
{
    /**
     * Escape HTML special characters
     */
    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape for use in HTML attributes
     */
    public static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape for use in JavaScript
     */
    public static function js(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Escape for use in URLs
     */
    public static function url(string $value): string
    {
        return filter_var($value, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize email address
     */
    public static function email(string $value): string
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Strip all HTML tags
     */
    public static function strip(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Sanitize integer
     */
    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public static function float(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize alphanumeric (letters and numbers only)
     */
    public static function alphanum(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    /**
     * Sanitize slug (lowercase letters, numbers, hyphens)
     */
    public static function slug(string $value): string
    {
        return \App\Core\Support\Str::slug($value);
    }

    /**
     * Remove null bytes
     */
    public static function nullBytes(string $value): string
    {
        return str_replace("\0", '', $value);
    }

    /**
     * Sanitize file path (prevent directory traversal)
     */
    public static function path(string $value): string
    {
        // Remove null bytes
        $value = self::nullBytes($value);
        
        // Remove directory traversal
        $value = str_replace(['../', '..\\', '..'], '', $value);
        
        // Normalize slashes
        $value = str_replace('\\', '/', $value);
        
        // Remove multiple slashes
        $value = preg_replace('#/+#', '/', $value);
        
        return $value;
    }

    /**
     * Sanitize filename
     */
    public static function filename(string $value): string
    {
        // Remove path components
        $value = basename($value);
        
        // Remove null bytes
        $value = self::nullBytes($value);
        
        // Remove dangerous characters
        $value = preg_replace('/[^a-zA-Z0-9._-]/', '', $value);
        
        return $value;
    }

    /**
     * Sanitize array of values
     */
    public static function array(array $values, string $method = 'html'): array
    {
        return array_map(function ($value) use ($method) {
            if (is_array($value)) {
                return self::array($value, $method);
            }
            
            if (is_string($value)) {
                return self::$method($value);
            }
            
            return $value;
        }, $values);
    }
}
