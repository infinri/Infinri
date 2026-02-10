<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Module;

use App\Core\Application;

/**
 * Module Loader
 *
 * Loads modules and registers their service providers, commands, and events.
 * Supports lazy loading for modules that should only load on-demand.
 */
class ModuleLoader
{
    protected Application $app;
    protected ModuleRegistry $registry;
    protected bool $loaded = false;

    /**
     * Loaded module commands
     *
     * @var array<string, string>
     */
    protected array $commands = [];

    /**
     * Modules that have been fully loaded
     *
     * @var array<string, bool>
     */
    protected array $loadedModules = [];

    /**
     * Deferred (lazy) modules awaiting load
     *
     * @var array<string, ModuleDefinition>
     */
    protected array $deferredModules = [];

    /**
     * Route prefix to module mapping for lazy loading
     *
     * @var array<string, string>
     */
    protected array $routePrefixMap = [];

    public function __construct(Application $app, ?ModuleRegistry $registry = null)
    {
        $this->app = $app;
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Load all enabled modules (eager modules immediately, lazy modules deferred)
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            if ($module->lazy) {
                // Defer lazy modules
                $this->deferModule($module);
            } else {
                // Load eager modules immediately
                $this->loadModule($module);
            }
        }

        $this->loaded = true;
    }

    /**
     * Defer a module for lazy loading
     */
    protected function deferModule(ModuleDefinition $module): void
    {
        $this->deferredModules[$module->name] = $module;

        // Map route prefixes to this module
        foreach ($module->routePrefixes as $prefix) {
            $this->routePrefixMap[$prefix] = $module->name;
        }

        // Still collect commands (they need to be available for console)
        foreach ($module->commands as $commandClass) {
            $this->commands[] = $commandClass;
        }
    }

    /**
     * Load a single module
     */
    protected function loadModule(ModuleDefinition $module): void
    {
        if (isset($this->loadedModules[$module->name])) {
            return; // Already loaded
        }

        // First load dependencies
        foreach ($module->dependencies as $dependency) {
            $depModule = $this->registry->get($dependency);
            if ($depModule !== null && ! isset($this->loadedModules[$dependency])) {
                $this->loadModule($depModule);
            }
        }

        // Register service providers
        foreach ($module->providers as $providerClass) {
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        // Collect commands for console registration
        if (! $module->lazy) {
            foreach ($module->commands as $commandClass) {
                if (class_exists($commandClass)) {
                    $this->commands[] = $commandClass;
                }
            }
        }

        $this->loadedModules[$module->name] = true;

        // Remove from deferred if it was there
        unset($this->deferredModules[$module->name]);
    }

    /**
     * Load a deferred module by name
     */
    public function loadDeferred(string $name): bool
    {
        if (isset($this->loadedModules[$name])) {
            return true; // Already loaded
        }

        if (! isset($this->deferredModules[$name])) {
            return false; // Not a deferred module
        }

        $this->loadModule($this->deferredModules[$name]);

        return true;
    }

    /**
     * Load module for a given route path (lazy loading trigger)
     */
    public function loadForRoute(string $path): void
    {
        foreach ($this->routePrefixMap as $prefix => $moduleName) {
            if (str_starts_with($path, $prefix) || $path === rtrim($prefix, '/')) {
                $this->loadDeferred($moduleName);
            }
        }
    }

    /**
     * Check if a module is loaded
     */
    public function isModuleLoaded(string $name): bool
    {
        return isset($this->loadedModules[$name]);
    }

    /**
     * Get deferred module names
     *
     * @return string[]
     */
    public function getDeferredModules(): array
    {
        return array_keys($this->deferredModules);
    }

    /**
     * Get loaded module names
     *
     * @return string[]
     */
    public function getLoadedModuleNames(): array
    {
        return array_keys($this->loadedModules);
    }

    /**
     * Get all module commands
     *
     * @return string[]
     */
    public function getCommands(): array
    {
        if (! $this->loaded) {
            $this->load();
        }

        return $this->commands;
    }

    /**
     * Get the module registry
     */
    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }

    /**
     * Check if modules have been loaded
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get all enabled modules
     *
     * @return ModuleDefinition[]
     */
    public function getModules(): array
    {
        if (! $this->loaded) {
            $this->load();
        }

        return $this->registry->getEnabled();
    }

    /**
     * Get a specific module
     */
    public function getModule(string $name): ?ModuleDefinition
    {
        return $this->registry->get($name);
    }
}
