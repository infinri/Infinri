<?php

declare(strict_types=1);

namespace App\Core\Module;

/**
 * Module Hook Runner
 * 
 * Executes module lifecycle hooks:
 * - onInstall() - First time setup
 * - onUpgrade($fromVersion) - Version upgrades
 * - onEnable() / onDisable() - State changes
 * - beforeSetup() / afterSetup() - Setup hooks
 */
class ModuleHookRunner
{
    protected ModuleRegistry $registry;
    protected string $statePath;
    protected array $state = [];

    public function __construct(ModuleRegistry $registry, ?string $statePath = null)
    {
        $this->registry = $registry;
        $this->statePath = $statePath ?? $this->getDefaultStatePath();
        $this->loadState();
    }

    /**
     * Run all pending hooks for setup
     */
    public function runSetupHooks(): array
    {
        $results = [
            'installed' => [],
            'upgraded' => [],
            'beforeSetup' => [],
            'afterSetup' => [],
        ];

        $this->registry->load();

        foreach ($this->registry->getEnabled() as $module) {
            // Check if new install
            if (!isset($this->state['installed'][$module->name])) {
                if ($this->runHook($module, 'onInstall')) {
                    $results['installed'][] = $module->name;
                }
                $this->state['installed'][$module->name] = $module->version;
            }
            // Check if upgrade needed
            elseif (version_compare($module->version, $this->state['installed'][$module->name], '>')) {
                $fromVersion = $this->state['installed'][$module->name];
                if ($this->runHook($module, 'onUpgrade', [$fromVersion])) {
                    $results['upgraded'][] = "{$module->name} ({$fromVersion} → {$module->version})";
                }
                $this->state['installed'][$module->name] = $module->version;
            }

            // Run beforeSetup
            if ($this->runHook($module, 'beforeSetup')) {
                $results['beforeSetup'][] = $module->name;
            }
        }

        $this->saveState();

        return $results;
    }

    /**
     * Run afterSetup hooks for all modules
     */
    public function runAfterSetupHooks(): array
    {
        $results = [];

        foreach ($this->registry->getEnabled() as $module) {
            if ($this->runHook($module, 'afterSetup')) {
                $results[] = $module->name;
            }
        }

        return $results;
    }

    /**
     * Run a specific hook on a module
     */
    public function runHook(ModuleDefinition $module, string $hook, array $args = []): bool
    {
        $hooksFile = $module->getFilePath('hooks.php');

        if (!file_exists($hooksFile)) {
            return false;
        }

        $hooks = require $hooksFile;

        if (!is_array($hooks) || !isset($hooks[$hook])) {
            return false;
        }

        $callback = $hooks[$hook];

        if (!is_callable($callback)) {
            return false;
        }

        try {
            $callback(...$args);
            return true;
        } catch (\Throwable $e) {
            // Log error but don't fail
            if (function_exists('log_error')) {
                log_error("Module hook failed: {$module->name}::{$hook}", [
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Run onEnable hook for a module
     */
    public function runEnableHook(string $name): bool
    {
        $module = $this->registry->get($name);
        if ($module === null) {
            return false;
        }

        return $this->runHook($module, 'onEnable');
    }

    /**
     * Run onDisable hook for a module
     */
    public function runDisableHook(string $name): bool
    {
        $module = $this->registry->get($name);
        if ($module === null) {
            return false;
        }

        return $this->runHook($module, 'onDisable');
    }

    /**
     * Mark a module as installed
     */
    public function markInstalled(string $name, string $version): void
    {
        $this->state['installed'][$name] = $version;
        $this->saveState();
    }

    /**
     * Get installed version of a module
     */
    public function getInstalledVersion(string $name): ?string
    {
        return $this->state['installed'][$name] ?? null;
    }

    /**
     * Check if module needs install
     */
    public function needsInstall(string $name): bool
    {
        return !isset($this->state['installed'][$name]);
    }

    /**
     * Check if module needs upgrade
     */
    public function needsUpgrade(ModuleDefinition $module): bool
    {
        $installed = $this->state['installed'][$module->name] ?? null;
        
        if ($installed === null) {
            return false;
        }

        return version_compare($module->version, $installed, '>');
    }

    /**
     * Load state from file
     */
    protected function loadState(): void
    {
        if (file_exists($this->statePath)) {
            $this->state = require $this->statePath;
        } else {
            $this->state = ['installed' => []];
        }
    }

    /**
     * Save state to file
     */
    protected function saveState(): void
    {
        save_php_array($this->statePath, $this->state, 'Module State');
    }

    protected function getDefaultStatePath(): string
    {
        return base_path('var/state/modules.php');
    }
}
