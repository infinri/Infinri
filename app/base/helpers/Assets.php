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
    
    private static array $headScripts = [];
    
    private static array $preconnectUrls = [];
    
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
        // Skip registration in production (bundles are used, saves memory)
        if (self::isProduction()) {
            return;
        }

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
        // Skip registration in production (bundles are used, saves memory)
        if (self::isProduction()) {
            return;
        }

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
     * Add external script to head section (e.g., for Google reCAPTCHA)
     *
     * @param string $url External script URL
     * @param array $attributes Additional attributes (async, defer, etc.)
     * @return void
     */
    public static function addHeadScript(string $url, array $attributes = []): void
    {
        self::$headScripts[] = [
            'url' => $url,
            'attributes' => $attributes
        ];
    }

    /**
     * Add preconnect hint for external origin (improves performance)
     *
     * @param string $url Origin URL (e.g., https://www.google.com)
     * @return void
     */
    public static function addPreconnect(string $url): void
    {
        if (!in_array($url, self::$preconnectUrls, true)) {
            self::$preconnectUrls[] = $url;
        }
    }

    /**
     * Render preconnect hints
     *
     * @return string
     */
    public static function renderPreconnects(): string
    {
        if (empty(self::$preconnectUrls)) {
            return '';
        }

        $output = '';
        foreach (self::$preconnectUrls as $url) {
            $output .= '<link rel="preconnect" href="' . Esc::html($url) . '" crossorigin>' . PHP_EOL;
        }

        return $output;
    }

    /**
     * Render head scripts
     *
     * @return string
     */
    public static function renderHeadScripts(): string
    {
        if (empty(self::$headScripts)) {
            return '';
        }

        $output = '';
        foreach (self::$headScripts as $script) {
            $output .= '<script src="' . Esc::html($script['url']) . '"';
            
            foreach ($script['attributes'] as $attr => $value) {
                if ($value === true) {
                    $output .= ' ' . $attr;
                } else {
                    $output .= ' ' . $attr . '="' . Esc::html($value) . '"';
                }
            }
            
            $output .= '></script>' . PHP_EOL;
        }

        return $output;
    }

    /**
     * Render inlined critical CSS with CSP nonce
     * Critical CSS is inlined for instant above-the-fold rendering (best LCP)
     *
     * @return string
     */
    public static function renderInlineCss(): string
    {
        // Get CSP nonce from globals (set in pub/index.php)
        $nonce = $GLOBALS['cspNonce'] ?? '';
        $nonceAttr = $nonce ? ' nonce="' . Esc::html($nonce) . '"' : '';
        
        $css = '';
        
        // Production: Use pre-minified critical.min.css
        if (self::isProduction()) {
            $criticalMinPath = dirname(__DIR__, 3) . '/pub/assets/dist/critical.min.css';
            if (file_exists($criticalMinPath)) {
                $css = file_get_contents($criticalMinPath);
            }
        } else {
            // Development: Minify critical.css on the fly
            $criticalPath = dirname(__DIR__) . '/view/base/css/critical.css';
            if (file_exists($criticalPath)) {
                $css = file_get_contents($criticalPath);
                // Minify: remove comments, whitespace, linebreaks
                $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
                $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
                $css = preg_replace('/\s{2,}/', ' ', $css);
                $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
                $css = trim($css);
            }
        }
        
        if (empty($css)) {
            return '';
        }
        
        $output = '<style' . $nonceAttr . '>' . $css . '</style>' . PHP_EOL;
        return $output;
    }

    /**
     * Render CSS tags - Critical CSS is inlined, non-critical loaded async
     * In production, uses minified bundles. In development, loads individual files.
     *
     * @return string
     */
    public static function renderCss(): string
    {
        $version = self::getVersion();
        $output = '';
        
        // Production: Load non-critical CSS (critical CSS is already inlined)
        if (self::isProduction()) {
            $allBundle = '/assets/dist/all.min.css?v=' . Esc::html($version);
            
            // Preload CSS for high-priority loading (tells browser to fetch immediately)
            $output .= '<link rel="preload" href="' . $allBundle . '" as="style">' . PHP_EOL;
            
            // Preload logo for instant LCP
            $output .= '<link rel="preload" href="/assets/base/images/logo.svg" as="image" fetchpriority="high">' . PHP_EOL;
            
            // Load full stylesheet - critical CSS renders instantly, this completes the styling
            $output .= '<link rel="stylesheet" href="' . $allBundle . '">' . PHP_EOL;
            
            return $output;
        }
        
        // Development: Load CSS normally for easier debugging
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
        self::$inlineCss = [];
        self::$headScripts = [];
        self::$version = null;
    }
}
