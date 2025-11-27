<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Middleware Compiler
 * 
 * Compiles middleware definitions from modules with priority ordering.
 * 
 * Module middleware.php format:
 * return [
 *     'global' => [
 *         \App\Http\Middleware\TrimStrings::class,
 *     ],
 *     'web' => [
 *         \App\Http\Middleware\VerifyCsrfToken::class => ['priority' => 100],
 *     ],
 *     'api' => [
 *         \App\Http\Middleware\ThrottleRequests::class => ['priority' => 50],
 *     ],
 *     'aliases' => [
 *         'auth' => \App\Http\Middleware\Authenticate::class,
 *         'throttle' => \App\Http\Middleware\ThrottleRequests::class,
 *     ],
 * ];
 */
class MiddlewareCompiler
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
        $this->cachePath = $cachePath ?? $this->basePath . '/var/cache/middleware.php';
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile all middleware into cache
     */
    public function compile(): array
    {
        $compiled = [
            'global' => [],
            'web' => [],
            'api' => [],
            'aliases' => [],
            'priority' => [],
        ];

        // Load app middleware first
        $compiled = $this->loadAppMiddleware($compiled);

        // Load module middleware
        $this->registry->load();
        foreach ($this->registry->getEnabled() as $module) {
            $compiled = $this->loadModuleMiddleware($module, $compiled);
        }

        // Sort by priority
        $compiled['global'] = $this->sortByPriority($compiled['global'], $compiled['priority']);
        $compiled['web'] = $this->sortByPriority($compiled['web'], $compiled['priority']);
        $compiled['api'] = $this->sortByPriority($compiled['api'], $compiled['priority']);

        // Remove priority map from final output
        unset($compiled['priority']);

        $this->saveToCache($compiled);

        return $compiled;
    }

    /**
     * Load app-level middleware
     */
    protected function loadAppMiddleware(array $compiled): array
    {
        $middlewarePath = $this->basePath . '/app/Http/middleware.php';

        if (file_exists($middlewarePath)) {
            $middleware = require $middlewarePath;
            $compiled = $this->mergeMiddleware($compiled, $middleware, 'app');
        }

        return $compiled;
    }

    /**
     * Load module middleware
     */
    protected function loadModuleMiddleware($module, array $compiled): array
    {
        $middlewarePath = $module->getFilePath('middleware.php');

        if (file_exists($middlewarePath)) {
            $middleware = require $middlewarePath;
            $compiled = $this->mergeMiddleware($compiled, $middleware, $module->name);
        }

        return $compiled;
    }

    /**
     * Merge middleware definitions
     */
    protected function mergeMiddleware(array $compiled, array $middleware, string $source): array
    {
        foreach (['global', 'web', 'api'] as $group) {
            if (isset($middleware[$group])) {
                foreach ($middleware[$group] as $key => $value) {
                    // Handle priority syntax: Class::class => ['priority' => 100]
                    if (is_array($value)) {
                        $class = $key;
                        $priority = $value['priority'] ?? 0;
                    } else {
                        $class = $value;
                        $priority = 0;
                    }

                    if (!in_array($class, $compiled[$group])) {
                        $compiled[$group][] = $class;
                        $compiled['priority'][$class] = $priority;
                    }
                }
            }
        }

        // Merge aliases
        if (isset($middleware['aliases'])) {
            $compiled['aliases'] = array_merge($compiled['aliases'], $middleware['aliases']);
        }

        return $compiled;
    }

    /**
     * Sort middleware by priority (higher first)
     */
    protected function sortByPriority(array $middleware, array $priorities): array
    {
        usort($middleware, function ($a, $b) use ($priorities) {
            $priorityA = $priorities[$a] ?? 0;
            $priorityB = $priorities[$b] ?? 0;
            return $priorityB <=> $priorityA;
        });

        return $middleware;
    }

    /**
     * Load compiled middleware
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
     * Clear cache
     */
    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get stats
     */
    public function getStats(): array
    {
        $compiled = $this->load();
        return [
            'global' => count($compiled['global']),
            'web' => count($compiled['web']),
            'api' => count($compiled['api']),
            'aliases' => count($compiled['aliases']),
        ];
    }

    protected function saveToCache(array $data): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content = "<?php\n\n// Compiled Middleware\n// Generated: " . date('Y-m-d H:i:s') . "\n\n"
            . "return " . var_export($data, true) . ";\n";

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
