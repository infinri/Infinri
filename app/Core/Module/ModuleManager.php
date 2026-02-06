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

use InvalidArgumentException;

/**
 * Module Manager
 *
 * @deprecated Use ModuleRegistry instead. This class will be removed in a future version.
 *
 * ModuleRegistry provides:
 * - Module metadata (module.php)
 * - Dependency ordering
 * - Caching to var/cache/modules.php
 * - Service provider discovery
 * @see ModuleRegistry
 * @see ModuleDefinition
 */
class ModuleManager
{
    /**
     * Discovered modules cache
     */
    protected ?array $discovered = null;

    /**
     * Modules directory path
     */
    protected string $modulesPath;

    /**
     * Loaded module instances
     */
    protected array $modules = [];

    public function __construct(?string $modulesPath = null)
    {
        $this->modulesPath = $modulesPath ?? $this->getDefaultModulesPath();
    }

    /**
     * Discover all available modules
     */
    public function discover(): array
    {
        if ($this->discovered !== null) {
            return $this->discovered;
        }

        $modules = [];

        if (! is_dir($this->modulesPath)) {
            return $this->discovered = [];
        }

        $items = scandir($this->modulesPath);
        $dirs = array_filter($items, fn ($item) => $item !== '.' && $item !== '..');

        foreach ($dirs as $dir) {
            $path = $this->modulesPath . '/' . $dir;

            // Must be a directory
            if (! is_dir($path)) {
                continue;
            }

            // Validate module name (lowercase alphanumeric with hyphens/underscores)
            if (! $this->isValidName($dir)) {
                continue;
            }

            // Must contain {ModuleName}Module.php
            if (file_exists($this->getClassFile($dir))) {
                $modules[] = $dir;
            }
        }

        return $this->discovered = $modules;
    }

    /**
     * Get module class file path
     */
    public function getClassFile(string $name): string
    {
        $this->validateName($name);
        $className = ucfirst($name) . 'Module.php';

        return $this->modulesPath . '/' . $name . '/' . $className;
    }

    /**
     * Get module class name (fully qualified)
     */
    public function getClassName(string $name): string
    {
        $moduleName = ucfirst($name);

        return "\\App\\Modules\\{$moduleName}\\{$moduleName}Module";
    }

    /**
     * Get module path
     */
    public function getPath(string $name, string $subPath = ''): string
    {
        $this->validateName($name);
        $path = $this->modulesPath . '/' . $name;

        if ($subPath !== '') {
            $path .= '/' . ltrim($subPath, '/');
        }

        return $path;
    }

    /**
     * Check if module exists
     */
    public function exists(string $name): bool
    {
        if (! $this->isValidName($name)) {
            return false;
        }

        return file_exists($this->getClassFile($name));
    }

    /**
     * Check if module has assets
     */
    public function hasAssets(string $name, string $context = 'frontend'): bool
    {
        if (! $this->isValidName($name)) {
            return false;
        }

        $assetsPath = $this->getPath($name, "view/{$context}");

        return is_dir($assetsPath);
    }

    /**
     * Get modules directory path
     */
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * Set modules directory path
     */
    public function setModulesPath(string $path): void
    {
        $this->modulesPath = $path;
        $this->discovered = null;
    }

    /**
     * Clear discovered modules cache
     */
    public function clearCache(): void
    {
        $this->discovered = null;
    }

    /**
     * Validate module name
     */
    protected function validateName(string $name): void
    {
        if (! $this->isValidName($name)) {
            throw new InvalidArgumentException("Invalid module name: {$name}");
        }
    }

    /**
     * Check if module name is valid
     */
    protected function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $name);
    }

    /**
     * Get default modules path
     */
    protected function getDefaultModulesPath(): string
    {
        return base_path('app/modules');
    }

    /**
     * Static discovery helper (backward compatible)
     */
    public static function discoverModules(): array
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance->discover();
    }
}
