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

    private static array $inlineCss = [];
    
    private static ?string $version = null;
    
    /**
     * Check if running in production mode
     *
     * @return bool
     */
    private static function isProduction(): bool
    {
        return Env::get('APP_ENV', 'development') === 'production';
    }

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
     * Mark CSS file to be inlined (for critical CSS)
     *
     * @param string $file File path (absolute from pub root)
     * @return void
     */
    public static function inlineCss(string $file): void
    {
        self::$inlineCss[] = $file;
    }

    /**
     * Render inlined critical CSS with CSP nonce
     *
     * @return string
     */
    public static function renderInlineCss(): string
    {
        // Don't render empty style tag (CSP violation)
        if (empty(self::$inlineCss)) {
            return '';
        }
        
        // Get CSP nonce from globals (set in pub/index.php)
        $nonce = $GLOBALS['cspNonce'] ?? '';
        $nonceAttr = $nonce ? ' nonce="' . Esc::html($nonce) . '"' : '';
        
        $output = '<style' . $nonceAttr . '>' . PHP_EOL;
        $pubPath = dirname(__DIR__, 3) . '/pub';
        
        foreach (self::$inlineCss as $file) {
            $filePath = $pubPath . $file;
            if (file_exists($filePath)) {
                $css = file_get_contents($filePath);
                // Remove comments and minify
                $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
                $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
                $css = preg_replace('/\s+/', ' ', $css);
                $output .= $css . PHP_EOL;
            }
        }
        
        $output .= '</style>' . PHP_EOL;
        return $output;
    }

    /**
     * Render CSS tags in correct order (base → frontend → module)
     * In production, uses minified bundles. In development, loads individual files.
     *
     * @return string
     */
    public static function renderCss(): string
    {
        $version = self::getVersion();
        $output = '';
        
        // Production: Inline critical CSS + preload full CSS for zero render blocking
        if (self::isProduction()) {
            $allBundle = '/assets/dist/all.min.css?v=' . Esc::html($version);
            
            // Preload logo for instant LCP (highest priority)
            $output .= '<link rel="preload" href="/assets/base/images/logo.svg" as="image" fetchpriority="high">' . PHP_EOL;
            
            // Load full CSS in head - simple and effective
            // This WILL block rendering BUT prevents all FOUC/shifts
            // Trade-off: Small render delay for perfect visual consistency
            $output .= '<link rel="stylesheet" href="' . $allBundle . '">' . PHP_EOL;
            
            return $output;
        }
        
        // Development: Load individual files for easier debugging
        $criticalFiles = [
            '/assets/base/css/critical-hero.css',
            '/assets/base/css/variables.css',
            '/assets/base/css/reset.css', 
            '/assets/base/css/base.css'
        ];
        
        // Load stylesheets directly (no preload hints to reduce overhead)
        foreach (['base', 'frontend', 'module'] as $layer) {
            foreach (self::$css[$layer] as $file) {
                if (in_array($file, self::$inlineCss, true)) {
                    continue;
                }
                
                $url = Esc::html($file) . '?v=' . Esc::html($version);
                
                if (in_array($file, $criticalFiles, true)) {
                    $output .= '<link rel="stylesheet" href="' . $url . '" fetchpriority="high">' . PHP_EOL;
                } else {
                    $output .= '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
                }
            }
        }

        return $output;
    }

    /**
     * Render JS tags in correct order (base → frontend → module)
     * In production, uses minified bundles. In development, loads individual files.
     *
     * @return string
     */
    public static function renderJs(): string
    {
        $version = self::getVersion();
        $output = '';
        
        // Production: Use single all-in-one bundle
        if (self::isProduction()) {
            // All-in-one bundle (base + frontend + all modules) - single request
            $allBundle = '/assets/dist/all.min.js?v=' . Esc::html($version);
            $output .= '<script src="' . $allBundle . '" defer></script>' . PHP_EOL;
            
            return $output;
        }

        // Development: Load individual files for easier debugging
        foreach (['base', 'frontend', 'module'] as $layer) {
            foreach (self::$js[$layer] as $file) {
                $url = Esc::html($file) . '?v=' . Esc::html($version);
                $output .= '<script src="' . $url . '" defer></script>' . PHP_EOL;
            }
        }

        return $output;
    }

    /**
     * Render full CSS at end of body (production only, non-blocking)
     * NOTE: No longer used - CSS loaded in head with async technique
     *
     * @return string
     */
    public static function renderFullCss(): string
    {
        // CSS now loaded in head - this method kept for compatibility
        return '';
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
