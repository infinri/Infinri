<?php

declare(strict_types=1);

namespace App\Core\View\Template;

use App\Core\Contracts\View\TemplateResolverInterface;

/**
 * Template Resolver
 * 
 * Resolves template paths with theme fallback chain:
 * 1. Theme override: Modules/Theme/view/{area}/templates/{module}/{template}
 * 2. Module template: Modules/{Module}/view/{area}/templates/{template}
 * 3. Core fallback: Core/View/view/{area}/templates/{template}
 * 
 * This enables:
 * - Theme customization without modifying modules
 * - Module-specific templates
 * - Core fallback templates
 */
final class TemplateResolver implements TemplateResolverInterface
{
    /**
     * Active theme name
     */
    private ?string $theme = 'Theme';

    /**
     * Base path to app directory
     */
    private string $appPath;

    /**
     * Resolved template cache
     * @var array<string, string|null>
     */
    private array $cache = [];

    public function __construct(?string $appPath = null)
    {
        $this->appPath = $appPath ?? $this->getDefaultAppPath();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $template, string $module, string $area = 'frontend'): ?string
    {
        $cacheKey = "{$this->theme}:{$area}:{$module}:{$template}";

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $paths = $this->getResolutionPaths($template, $module, $area);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->cache[$cacheKey] = $path;
                return $path;
            }
        }

        $this->cache[$cacheKey] = null;
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $template, string $module, string $area = 'frontend'): bool
    {
        return $this->resolve($template, $module, $area) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function setTheme(?string $theme): void
    {
        if ($this->theme !== $theme) {
            $this->theme = $theme;
            $this->cache = []; // Clear cache on theme change
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolutionPaths(string $template, string $module, string $area = 'frontend'): array
    {
        $paths = [];
        $moduleLower = strtolower($module);

        // 1. Theme override (if theme is set)
        if ($this->theme !== null) {
            $paths[] = $this->appPath . "/Modules/{$this->theme}/view/{$area}/templates/{$moduleLower}/{$template}";
        }

        // 2. Module template
        $paths[] = $this->appPath . "/Modules/{$module}/view/{$area}/templates/{$template}";

        // 3. Core fallback
        $paths[] = $this->appPath . "/Core/View/view/{$area}/templates/{$template}";

        // 4. Legacy lowercase module path (backwards compatibility)
        $paths[] = $this->appPath . "/modules/{$moduleLower}/view/{$area}/templates/{$template}";

        return $paths;
    }

    /**
     * Resolve a layout file
     * 
     * Similar to template resolution but for layout files.
     * 
     * @param string $layout Layout name (e.g., 'default.xml', '1column.xml')
     * @param string $area Area name
     * @return string|null Resolved absolute path or null if not found
     */
    public function resolveLayout(string $layout, string $area = 'frontend'): ?string
    {
        $paths = $this->getLayoutResolutionPaths($layout, $area);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get layout resolution paths
     * 
     * @param string $layout Layout name
     * @param string $area Area name
     * @return string[] Array of paths to check
     */
    public function getLayoutResolutionPaths(string $layout, string $area = 'frontend'): array
    {
        $paths = [];

        // 1. Theme layout
        if ($this->theme !== null) {
            $paths[] = $this->appPath . "/Modules/{$this->theme}/view/{$area}/layout/{$layout}";
        }

        // 2. Core layout
        $paths[] = $this->appPath . "/Core/View/view/{$area}/layout/{$layout}";

        return $paths;
    }

    /**
     * Resolve a static asset (CSS, JS, images)
     * 
     * @param string $asset Asset path relative to web/ directory
     * @param string $module Module name
     * @param string $area Area name
     * @return string|null Resolved absolute path or null if not found
     */
    public function resolveAsset(string $asset, string $module, string $area = 'frontend'): ?string
    {
        $paths = $this->getAssetResolutionPaths($asset, $module, $area);

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get asset resolution paths
     * 
     * @param string $asset Asset path
     * @param string $module Module name
     * @param string $area Area name
     * @return string[] Array of paths to check
     */
    public function getAssetResolutionPaths(string $asset, string $module, string $area = 'frontend'): array
    {
        $paths = [];
        $moduleLower = strtolower($module);

        // 1. Theme override
        if ($this->theme !== null) {
            $paths[] = $this->appPath . "/Modules/{$this->theme}/view/{$area}/web/{$moduleLower}/{$asset}";
        }

        // 2. Module asset
        $paths[] = $this->appPath . "/Modules/{$module}/view/{$area}/web/{$asset}";

        // 3. Core asset
        $paths[] = $this->appPath . "/Core/View/view/{$area}/web/{$asset}";

        return $paths;
    }

    /**
     * Clear the resolution cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get default app path
     */
    private function getDefaultAppPath(): string
    {
        return defined('BASE_PATH') ? BASE_PATH . '/app' : dirname(__DIR__, 3);
    }
}
