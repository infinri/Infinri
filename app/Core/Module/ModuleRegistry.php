<?php

declare(strict_types=1);

namespace App\Core\Module;

/**
 * Module Registry
 * 
 * Scans, registers, and caches module metadata.
 * Handles dependency ordering and module state.
 */
class ModuleRegistry
{
    /**
     * Registered modules (keyed by name)
     * @var array<string, ModuleDefinition>
     */
    protected array $modules = [];

    /**
     * Modules in dependency-sorted order
     * @var string[]
     */
    protected array $loadOrder = [];

    /**
     * Path to modules directory
     */
    protected string $modulesPath;

    /**
     * Path to cache file
     */
    protected string $cachePath;

    /**
     * Whether registry is loaded
     */
    protected bool $loaded = false;

    public function __construct(?string $modulesPath = null, ?string $cachePath = null)
    {
        $this->modulesPath = $modulesPath ?? $this->getDefaultModulesPath();
        $this->cachePath = $cachePath ?? $this->getDefaultCachePath();
    }

    /**
     * Load the module registry (from cache or scan)
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // Try to load from cache
        if ($this->loadFromCache()) {
            $this->loaded = true;
            return;
        }

        // Scan and build registry
        $this->scan();
        $this->resolveLoadOrder();
        $this->saveToCache();
        
        $this->loaded = true;
    }

    /**
     * Scan modules directory and register all modules
     */
    public function scan(): void
    {
        $this->modules = [];

        if (!is_dir($this->modulesPath)) {
            return;
        }

        $dirs = array_filter(
            scandir($this->modulesPath),
            fn($d) => $d !== '.' && $d !== '..' && is_dir($this->modulesPath . '/' . $d)
        );

        foreach ($dirs as $dir) {
            $modulePath = $this->modulesPath . '/' . $dir;
            $moduleFile = $modulePath . '/module.php';

            // Load module.php if exists, otherwise create default definition
            if (file_exists($moduleFile)) {
                $data = require $moduleFile;
            } else {
                // Fallback: check for legacy *Module.php class file
                $legacyFile = $modulePath . '/' . ucfirst($dir) . 'Module.php';
                if (!file_exists($legacyFile)) {
                    continue; // Not a valid module
                }
                $data = ['name' => $dir];
            }

            $definition = new ModuleDefinition($data, $modulePath);
            $this->modules[$definition->name] = $definition;
        }
    }

    /**
     * Resolve module load order based on dependencies
     */
    public function resolveLoadOrder(): void
    {
        $this->loadOrder = [];
        $resolved = [];
        $unresolved = [];

        foreach ($this->modules as $name => $module) {
            if ($module->enabled && !isset($resolved[$name])) {
                $this->resolveDependencies($name, $resolved, $unresolved);
            }
        }
    }

    /**
     * Topological sort for dependency resolution
     */
    protected function resolveDependencies(string $name, array &$resolved, array &$unresolved): void
    {
        $unresolved[$name] = true;

        $module = $this->modules[$name] ?? null;
        if ($module === null) {
            throw new \RuntimeException("Module not found: {$name}");
        }

        foreach ($module->dependencies as $dependency) {
            if (!isset($this->modules[$dependency])) {
                throw new \RuntimeException("Module '{$name}' depends on missing module: {$dependency}");
            }

            if (!isset($resolved[$dependency])) {
                if (isset($unresolved[$dependency])) {
                    throw new \RuntimeException("Circular dependency detected: {$name} <-> {$dependency}");
                }
                $this->resolveDependencies($dependency, $resolved, $unresolved);
            }
        }

        $resolved[$name] = true;
        unset($unresolved[$name]);
        $this->loadOrder[] = $name;
    }

    /**
     * Get all registered modules
     * @return array<string, ModuleDefinition>
     */
    public function all(): array
    {
        $this->load();
        return $this->modules;
    }

    /**
     * Get enabled modules in load order
     * @return ModuleDefinition[]
     */
    public function getEnabled(): array
    {
        $this->load();
        
        $enabled = [];
        foreach ($this->loadOrder as $name) {
            if (isset($this->modules[$name]) && $this->modules[$name]->enabled) {
                $enabled[] = $this->modules[$name];
            }
        }
        return $enabled;
    }

    /**
     * Get a specific module
     */
    public function get(string $name): ?ModuleDefinition
    {
        $this->load();
        return $this->modules[$name] ?? null;
    }

    /**
     * Check if module exists
     */
    public function has(string $name): bool
    {
        $this->load();
        return isset($this->modules[$name]);
    }

    /**
     * Get module names in load order
     */
    public function getLoadOrder(): array
    {
        $this->load();
        return $this->loadOrder;
    }

    /**
     * Enable a module
     */
    public function enable(string $name): bool
    {
        $this->load();
        
        if (!isset($this->modules[$name])) {
            return false;
        }

        // Update enabled state in module.php
        $modulePath = $this->modules[$name]->path . '/module.php';
        if (file_exists($modulePath)) {
            $this->updateModuleEnabled($modulePath, true);
        }

        // Rebuild registry
        $this->rebuild();
        return true;
    }

    /**
     * Disable a module
     */
    public function disable(string $name): bool
    {
        $this->load();
        
        if (!isset($this->modules[$name])) {
            return false;
        }

        // Update enabled state in module.php
        $modulePath = $this->modules[$name]->path . '/module.php';
        if (file_exists($modulePath)) {
            $this->updateModuleEnabled($modulePath, false);
        }

        // Rebuild registry
        $this->rebuild();
        return true;
    }

    /**
     * Update enabled state in module.php
     */
    protected function updateModuleEnabled(string $path, bool $enabled): void
    {
        $content = file_get_contents($path);
        $replacement = "'enabled' => " . ($enabled ? 'true' : 'false');
        
        // Replace existing enabled line
        $content = preg_replace(
            "/'enabled'\s*=>\s*(true|false)/",
            $replacement,
            $content
        );

        file_put_contents($path, $content);
    }

    /**
     * Rebuild the registry (force re-scan)
     */
    public function rebuild(): void
    {
        $this->loaded = false;
        $this->modules = [];
        $this->loadOrder = [];
        
        $this->scan();
        $this->resolveLoadOrder();
        $this->saveToCache();
        
        $this->loaded = true;
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
        $this->loaded = false;
    }

    /**
     * Load registry from cache file
     */
    protected function loadFromCache(): bool
    {
        if (!file_exists($this->cachePath)) {
            return false;
        }

        try {
            $data = require $this->cachePath;
            
            if (!is_array($data) || !isset($data['modules'], $data['loadOrder'])) {
                return false;
            }

            foreach ($data['modules'] as $name => $moduleData) {
                $this->modules[$name] = ModuleDefinition::fromArray($moduleData);
            }
            
            $this->loadOrder = $data['loadOrder'];
            return true;
        } catch (\Throwable $e) {
            if (function_exists('logger')) {
                logger()->warning('Module registry cache load failed', ['error' => $e->getMessage()]);
            }
            return false;
        }
    }

    /**
     * Save registry to cache file
     */
    protected function saveToCache(): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $modules = [];
        foreach ($this->modules as $name => $module) {
            $modules[$name] = $module->toArray();
        }

        $content = "<?php\n\n// Generated: " . date('Y-m-d H:i:s') . "\n// DO NOT EDIT\n\nreturn " 
            . var_export([
                'modules' => $modules,
                'loadOrder' => $this->loadOrder,
            ], true) . ";\n";

        file_put_contents($this->cachePath, $content);
    }

    protected function getDefaultModulesPath(): string
    {
        return base_path('app/modules');
    }

    protected function getDefaultCachePath(): string
    {
        return base_path('var/cache/modules.php');
    }
}
