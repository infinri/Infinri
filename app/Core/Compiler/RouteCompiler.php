<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Route Compiler
 * 
 * Compiles routes from modules into a cached file for faster resolution.
 */
class RouteCompiler
{
    protected string $basePath;
    protected string $cachePath;
    protected ModuleRegistry $registry;

    public function __construct(
        ?string $basePath = null,
        ?string $cachePath = null,
        ?ModuleRegistry $registry = null
    ) {
        $this->basePath = $basePath ?? $this->getDefaultBasePath();
        $this->cachePath = $cachePath ?? $this->basePath . '/var/cache/routes.php';
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile all routes into cache
     */
    public function compile(): array
    {
        $routes = [
            'web' => [],
            'api' => [],
        ];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            $moduleRoutes = $module->loadRoutes();
            
            if (!empty($moduleRoutes)) {
                // Namespace routes under module
                foreach ($moduleRoutes as $type => $typeRoutes) {
                    if (in_array($type, ['web', 'api'])) {
                        foreach ($typeRoutes as $route) {
                            $route['module'] = $module->name;
                            $routes[$type][] = $route;
                        }
                    }
                }
            }
        }

        $this->saveToCache($routes);

        return $routes;
    }

    /**
     * Load compiled routes
     */
    public function load(): array
    {
        if ($this->isCached()) {
            return $this->loadFromCache();
        }

        return $this->compile();
    }

    /**
     * Check if cache exists
     */
    public function isCached(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Clear route cache
     */
    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get route count
     */
    public function getStats(): array
    {
        $routes = $this->load();
        return [
            'web' => count($routes['web']),
            'api' => count($routes['api']),
            'total' => count($routes['web']) + count($routes['api']),
        ];
    }

    protected function saveToCache(array $routes): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content = "<?php\n\n// Compiled Routes\n// Generated: " . date('Y-m-d H:i:s') . "\n\n"
            . "return " . var_export($routes, true) . ";\n";

        file_put_contents($this->cachePath, $content);
    }

    protected function loadFromCache(): array
    {
        return require $this->cachePath;
    }

    protected function getDefaultBasePath(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath();
            } catch (\Throwable) {}
        }
        return dirname(__DIR__, 3);
    }
}
