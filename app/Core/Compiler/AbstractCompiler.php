<?php

declare(strict_types=1);

namespace App\Core\Compiler;

use App\Core\Module\ModuleRegistry;

/**
 * Abstract Compiler
 * 
 * Base class for all compilers providing common caching functionality.
 * Eliminates duplicate code across ConfigCompiler, EventCompiler, etc.
 */
abstract class AbstractCompiler
{
    protected string $basePath;
    protected string $cachePath;
    protected ModuleRegistry $registry;

    public function __construct(
        ?string $basePath = null,
        ?string $cachePath = null,
        ?ModuleRegistry $registry = null
    ) {
        $this->basePath = $basePath ?? base_path();
        $this->cachePath = $cachePath ?? $this->getDefaultCachePath();
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Compile and cache - implemented by each compiler
     */
    abstract public function compile(): array;

    /**
     * Get the default cache path for this compiler
     */
    abstract protected function getDefaultCachePath(): string;

    /**
     * Load from cache or compile if not cached
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
     * Clear the cache
     */
    public function clear(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get cache path
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * Save data to cache file
     */
    protected function saveToCache(array $data, string $header = 'Compiled Data'): void
    {
        save_php_array($this->cachePath, $data, $header);
    }

    /**
     * Load data from cache file
     */
    protected function loadFromCache(): array
    {
        return require $this->cachePath;
    }
}
