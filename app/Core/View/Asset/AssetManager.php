<?php
declare(strict_types=1);
/**
 * Asset Manager
 * 
 * Manages CSS and JS assets with:
 * - Dev mode: Individual files for debugging
 * - Prod mode: Bundled/minified files for performance
 * - CSP nonce support for inline scripts/styles
 * - Module-based asset resolution
 * 
 * @package App\Core\View\Asset
 */

namespace App\Core\View\Asset;

final class AssetManager
{
    /**
     * Whether we're in production mode
     */
    private bool $isProduction;
    
    /**
     * CSP nonce for inline scripts/styles
     */
    private ?string $cspNonce;
    
    /**
     * CSS files queue
     * @var array<string>
     */
    private array $cssFiles = [];
    
    /**
     * JS files queue
     * @var array<string>
     */
    private array $jsFiles = [];
    
    /**
     * Head scripts (external APIs, etc.)
     * @var array<array{url: string, attributes: array}>
     */
    private array $headScripts = [];
    
    /**
     * Preconnect URLs
     * @var array<string>
     */
    private array $preconnects = [];
    
    /**
     * Current area (frontend/admin)
     */
    private string $area = 'frontend';
    
    /**
     * Asset version for cache busting
     */
    private ?string $version = null;
    
    public function __construct(?string $cspNonce = null)
    {
        $this->isProduction = env('APP_ENV', 'production') === 'production';
        $this->cspNonce = $cspNonce ?? csp_nonce();
    }
    
    /**
     * Set CSP nonce (for deferred initialization)
     */
    public function setNonce(?string $nonce): static
    {
        $this->cspNonce = $nonce;
        return $this;
    }
    
    /**
     * Set the current area
     * 
     * @param string $area Area name (frontend/admin)
     * @return static
     */
    public function setArea(string $area): static
    {
        $this->area = $area;
        return $this;
    }
    
    /**
     * Get current area
     * 
     * @return string
     */
    public function getArea(): string
    {
        return $this->area;
    }
    
    /**
     * Set asset version for cache busting
     * 
     * @param string $version Version string
     * @return static
     */
    public function setVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }
    
    /**
     * Add CSS file from module
     * 
     * @param string $file Module::path notation (e.g., "Home::css/hero.css")
     * @return static
     */
    public function addCss(string $file): static
    {
        // In production, skip individual files (use bundles)
        if ($this->isProduction) {
            return $this;
        }
        
        $resolved = $this->resolveAssetPath($file);
        if (!in_array($resolved, $this->cssFiles, true)) {
            $this->cssFiles[] = $resolved;
        }
        
        return $this;
    }
    
    /**
     * Add JS file from module
     * 
     * @param string $file Module::path notation
     * @return static
     */
    public function addJs(string $file): static
    {
        // In production, skip individual files (use bundles)
        if ($this->isProduction) {
            return $this;
        }
        
        $resolved = $this->resolveAssetPath($file);
        if (!in_array($resolved, $this->jsFiles, true)) {
            $this->jsFiles[] = $resolved;
        }
        
        return $this;
    }
    
    /**
     * Add external head script
     * 
     * @param string $url Script URL
     * @param array<string, mixed> $attributes Additional attributes
     * @return static
     */
    public function addHeadScript(string $url, array $attributes = []): static
    {
        $this->headScripts[] = [
            'url' => $url,
            'attributes' => $attributes,
        ];
        return $this;
    }
    
    /**
     * Add preconnect hint
     * 
     * @param string $url Origin URL
     * @return static
     */
    public function addPreconnect(string $url): static
    {
        if (!in_array($url, $this->preconnects, true)) {
            $this->preconnects[] = $url;
        }
        return $this;
    }
    
    /**
     * Resolve Module::path notation to URL
     * 
     * @param string $file Asset path
     * @return string Public URL
     */
    private function resolveAssetPath(string $file): string
    {
        if (str_contains($file, '::')) {
            [$module, $path] = explode('::', $file, 2);
            return "/assets/modules/{$module}/view/{$this->area}/{$path}";
        }
        
        // Already a URL path
        return $file;
    }
    
    /**
     * Get asset version for cache busting
     * 
     * @return string
     */
    private function getVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }
        
        return env('APP_VERSION', (string) time());
    }
    
    /**
     * Render preconnect hints
     * 
     * @return string HTML
     */
    public function renderPreconnects(): string
    {
        if (empty($this->preconnects)) {
            return '';
        }
        
        $output = '';
        foreach ($this->preconnects as $url) {
            $output .= '<link rel="preconnect" href="' . e($url) . '" crossorigin>' . PHP_EOL;
        }
        
        return $output;
    }
    
    /**
     * Render critical CSS inline (with CSP nonce)
     * 
     * @return string HTML
     */
    public function renderCriticalCss(): string
    {
        $criticalPath = $this->isProduction
            ? public_path("assets/dist/critical-{$this->area}.css")
            : app_path("Core/View/view/{$this->area}/web/css/critical.css");
        
        if (!file_exists($criticalPath)) {
            // Fallback to base critical CSS
            $criticalPath = app_path('Core/View/view/base/web/css/critical.css');
            if (!file_exists($criticalPath)) {
                return '';
            }
        }
        
        $css = file_get_contents($criticalPath);
        
        // Minify if not production (production is pre-minified)
        if (!$this->isProduction) {
            $css = $this->minifyCss($css);
        }
        
        return $this->renderInlineStyle($css);
    }
    
    /**
     * Render CSS includes
     * 
     * @return string HTML
     */
    public function renderCss(): string
    {
        $version = $this->getVersion();
        $output = '';
        
        if ($this->isProduction) {
            // Single bundle in production
            $bundle = "/assets/dist/{$this->area}.min.css?v=" . e($version);
            $output .= '<link rel="stylesheet" href="' . $bundle . '">' . PHP_EOL;
        } else {
            // Individual files in development
            foreach ($this->cssFiles as $file) {
                $url = e($file) . '?v=' . e($version);
                $output .= '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
            }
        }
        
        return $output;
    }
    
    /**
     * Render head scripts
     * 
     * @return string HTML
     */
    public function renderHeadScripts(): string
    {
        if (empty($this->headScripts)) {
            return '';
        }
        
        $output = '';
        foreach ($this->headScripts as $script) {
            $output .= '<script src="' . e($script['url']) . '"';
            
            foreach ($script['attributes'] as $attr => $value) {
                if ($value === true) {
                    $output .= ' ' . e($attr);
                } elseif ($value !== false && $value !== null) {
                    $output .= ' ' . e($attr) . '="' . e($value) . '"';
                }
            }
            
            $output .= '></script>' . PHP_EOL;
        }
        
        return $output;
    }
    
    /**
     * Render JS includes (at end of body)
     * 
     * @return string HTML
     */
    public function renderJs(): string
    {
        $version = $this->getVersion();
        $output = '';
        
        if ($this->isProduction) {
            // Single bundle in production
            $bundle = "/assets/dist/{$this->area}.min.js?v=" . e($version);
            $output .= '<script src="' . $bundle . '" defer></script>' . PHP_EOL;
        } else {
            // Individual files in development
            foreach ($this->jsFiles as $file) {
                $url = e($file) . '?v=' . e($version);
                $output .= '<script src="' . $url . '" defer></script>' . PHP_EOL;
            }
        }
        
        return $output;
    }
    
    /**
     * Render inline style with CSP nonce
     * 
     * Use sparingly - prefer external CSS files.
     * 
     * @param string $css CSS content
     * @return string HTML
     */
    public function renderInlineStyle(string $css): string
    {
        if (empty(trim($css))) {
            return '';
        }
        
        $nonceAttr = $this->cspNonce ? ' nonce="' . e($this->cspNonce) . '"' : '';
        return '<style' . $nonceAttr . '>' . $css . '</style>' . PHP_EOL;
    }
    
    /**
     * Render inline script with CSP nonce
     * 
     * Use sparingly - prefer external JS files.
     * 
     * @param string $script JS content
     * @return string HTML
     */
    public function renderInlineScript(string $script): string
    {
        if (empty(trim($script))) {
            return '';
        }
        
        $nonceAttr = $this->cspNonce ? ' nonce="' . e($this->cspNonce) . '"' : '';
        return '<script' . $nonceAttr . '>' . $script . '</script>' . PHP_EOL;
    }
    
    /**
     * Get CSP nonce
     * 
     * @return string|null
     */
    public function getNonce(): ?string
    {
        return $this->cspNonce;
    }
    
    /**
     * Check if running in production
     * 
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->isProduction;
    }
    
    /**
     * Simple CSS minification
     * 
     * @param string $css CSS content
     * @return string Minified CSS
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s{2,}/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        return trim($css);
    }
    
    /**
     * Reset state
     * 
     * @return static
     */
    public function reset(): static
    {
        $this->cssFiles = [];
        $this->jsFiles = [];
        $this->headScripts = [];
        $this->preconnects = [];
        return $this;
    }
}
