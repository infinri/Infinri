<?php
declare(strict_types=1);
/**
 * Escape Helper
 *
 * Context-aware output escaping for XSS prevention
 *
 * @package App\Helpers
 */

namespace App\Helpers;

final class Esc
{
    /**
     * Escape for HTML/attribute context
     * Use for both HTML content and HTML attributes
     *
     * @param string $value Value to escape
     * @return string
     */
    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape for URL context
     *
     * @param string $value Value to escape
     * @return string
     */
    public static function url(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * Escape for JavaScript context
     *
     * @param string $value Value to escape
     * @return string
     */
    public static function js(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }
}
