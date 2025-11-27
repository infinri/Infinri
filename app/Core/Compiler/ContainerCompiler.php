<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Container Compiler
 * 
 * Compiles service provider bindings for faster container resolution.
 * 
 * This scans all module providers and extracts:
 * - Binding definitions
 * - Singleton definitions
 * - Alias definitions
 * - Deferred services
 */
class ContainerCompiler
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
        $this->cachePath = $cachePath ?? $this->basePath . '/var/cache/container.php';
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile container bindings
     */
    public function compile(): array
    {
        $compiled = [
            'providers' => [],
            'deferred' => [],
            'provides' => [],
        ];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            foreach ($module->providers as $providerClass) {
                if (!class_exists($providerClass)) {
                    continue;
                }

                $compiled['providers'][] = [
                    'class' => $providerClass,
                    'module' => $module->name,
                ];

                // Check if provider is deferred
                try {
                    $reflection = new \ReflectionClass($providerClass);
                    
                    // Check for isDeferred method
                    if ($reflection->hasMethod('isDeferred')) {
                        $instance = $reflection->newInstanceWithoutConstructor();
                        if ($instance->isDeferred()) {
                            $compiled['deferred'][] = $providerClass;
                            
                            // Get provided services
                            if ($reflection->hasMethod('provides')) {
                                $provides = $instance->provides();
                                foreach ($provides as $service) {
                                    $compiled['provides'][$service] = $providerClass;
                                }
                            }
                        }
                    }
                } catch (\Throwable) {
                    // Skip if can't reflect
                }
            }
        }

        $this->saveToCache($compiled);

        return $compiled;
    }

    /**
     * Load compiled container data
     */
    public function load(): array
    {
        if ($this->isCached()) {
            return $this->loadFromCache();
        }

        return $this->compile();
    }

    /**
     * Check if compiled cache exists
     */
    public function isCached(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Clear the container cache
     */
    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get provider count
     */
    public function getStats(): array
    {
        $data = $this->load();
        
        return [
            'total_providers' => count($data['providers']),
            'deferred_providers' => count($data['deferred']),
            'deferred_services' => count($data['provides']),
        ];
    }

    /**
     * Save compiled data to cache
     */
    protected function saveToCache(array $data): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $content = "<?php\n\n"
            . "// Compiled Container - Generated: " . date('Y-m-d H:i:s') . "\n"
            . "// DO NOT EDIT - Run 'php bin/console s:up' to regenerate\n\n"
            . "return " . var_export($data, true) . ";\n";

        file_put_contents($this->cachePath, $content);
    }

    /**
     * Load from cache
     */
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
