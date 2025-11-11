<?php
declare(strict_types=1);
/**
 * Assets Helper
 *
 * Manages CSS and JavaScript assets with cascade loading
 * and security validation
 *
 * @package App\Base\Helpers
 */

namespace App\Base\Helpers;

use App\Helpers\{Esc, Env};

final class Assets
{
    private static array $css = [
        'base' => [],
        'frontend' => [],
        'module' => []
    ];

    private static array $js = [
        'base' => [],
        'frontend' => [],
        'module' => []
    ];

    private static ?string $version = null;

    /**
     * Set asset version for cache busting
     *
     * @param string $version Version string
     * @return void
     */
    public static function setVersion(string $version): void
    {
        self::$version = $version;
    }

    /**
     * Get current asset version
     *
     * @return string
     */
    private static function getVersion(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        // Use APP_VERSION from env, or timestamp in dev
        return Env::get('APP_VERSION', (string)time());
    }

    /**
     * Validate asset path for security
     *
     * @param string $file File path
     * @return void
     * @throws \InvalidArgumentException
     */
    private static function validatePath(string $file): void
    {
        // Block directory traversal
        if (strpos($file, '..') !== false) {
            throw new \InvalidArgumentException('Asset path cannot contain ".."');
        }

        // Block null bytes
        if (strpos($file, "\0") !== false) {
            throw new \InvalidArgumentException('Asset path cannot contain null bytes');
        }

        // Must be absolute path from pub root
        if (strlen($file) === 0 || $file[0] !== '/') {
            throw new \InvalidArgumentException('Asset path must start with "/"');
        }
    }

    /**
     * Add CSS file to specific layer
     *
     * @param string $file File path (absolute from pub root)
     * @param string $layer Layer: base|frontend|module
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function addCss(string $file, string $layer = 'module'): void
    {
        self::validatePath($file);

        if (! in_array($file, self::$css[$layer], true)) {
            self::$css[$layer][] = $file;
        }
    }

    /**
     * Add JS file to specific layer
     *
     * @param string $file File path (absolute from pub root)
     * @param string $layer Layer: base|frontend|module
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function addJs(string $file, string $layer = 'module'): void
    {
        self::validatePath($file);

        if (! in_array($file, self::$js[$layer], true)) {
            self::$js[$layer][] = $file;
        }
    }

    /**
     * Render CSS tags in correct order (base → frontend → module)
     *
     * @return string
     */
    public static function renderCss(): string
    {
        $version = self::getVersion();
        $output = '';

        foreach (['base', 'frontend', 'module'] as $layer) {
            foreach (self::$css[$layer] as $file) {
                $url = Esc::html($file) . '?v=' . Esc::html($version);
                $output .= '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
            }
        }

        return $output;
    }

    /**
     * Render JS tags in correct order (base → frontend → module)
     *
     * @return string
     */
    public static function renderJs(): string
    {
        $version = self::getVersion();
        $output = '';

        foreach (['base', 'frontend', 'module'] as $layer) {
            foreach (self::$js[$layer] as $file) {
                $url = Esc::html($file) . '?v=' . Esc::html($version);
                $output .= '<script src="' . $url . '"></script>' . PHP_EOL;
            }
        }

        return $output;
    }

    /**
     * Clear all assets (useful for testing)
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$css = ['base' => [], 'frontend' => [], 'module' => []];
        self::$js = ['base' => [], 'frontend' => [], 'module' => []];
        self::$version = null;
    }
}
