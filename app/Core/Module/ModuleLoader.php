<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Application;
use App\Core\Container\ServiceProvider;

/**
 * Module Loader
 * 
 * Loads modules and registers their service providers, commands, and events.
 */
class ModuleLoader
{
    protected Application $app;
    protected ModuleRegistry $registry;
    protected bool $loaded = false;

    /**
     * Loaded module commands
     * @var array<string, string>
     */
    protected array $commands = [];

    public function __construct(Application $app, ?ModuleRegistry $registry = null)
    {
        $this->app = $app;
        $this->registry = $registry ?? new ModuleRegistry();
    }

    /**
     * Load all enabled modules
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->registry->load();
        
        foreach ($this->registry->getEnabled() as $module) {
            $this->loadModule($module);
        }

        $this->loaded = true;
    }

    /**
     * Load a single module
     */
    protected function loadModule(ModuleDefinition $module): void
    {
        // Register service providers
        foreach ($module->providers as $providerClass) {
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        // Collect commands for console registration
        foreach ($module->commands as $commandClass) {
            if (class_exists($commandClass)) {
                $this->commands[] = $commandClass;
            }
        }
    }

    /**
     * Get all module commands
     * 
     * @return string[]
     */
    public function getCommands(): array
    {
        if (!$this->loaded) {
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
        if (!$this->loaded) {
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
