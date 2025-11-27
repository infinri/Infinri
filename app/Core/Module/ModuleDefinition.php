<?php

declare(strict_types=1);

namespace App\Core\Module;

/**
 * Module Definition
 * 
 * Represents a module's metadata loaded from module.php
 */
class ModuleDefinition
{
    public readonly string $name;
    public readonly string $version;
    public readonly string $description;
    public readonly string $path;
    public readonly array $dependencies;
    public readonly array $providers;
    public readonly array $commands;
    public readonly ?string $eventsFile;
    public readonly ?string $configFile;
    public readonly ?string $routesFile;
    public readonly bool $enabled;

    public function __construct(array $data, string $path)
    {
        $this->name = $data['name'] ?? basename($path);
        $this->version = $data['version'] ?? '1.0.0';
        $this->description = $data['description'] ?? '';
        $this->path = $path;
        $this->dependencies = $data['dependencies'] ?? [];
        $this->providers = $data['providers'] ?? [];
        $this->commands = $data['commands'] ?? [];
        $this->eventsFile = $data['events'] ?? null;
        $this->configFile = $data['config'] ?? null;
        $this->routesFile = $data['routes'] ?? null;
        $this->enabled = $data['enabled'] ?? true;
    }

    /**
     * Get the full path to a file within the module
     */
    public function getFilePath(string $relativePath): string
    {
        return $this->path . '/' . ltrim($relativePath, '/');
    }

    /**
     * Check if module has a specific file
     */
    public function hasFile(string $relativePath): bool
    {
        return file_exists($this->getFilePath($relativePath));
    }

    /**
     * Get the module class file path (*Module.php)
     */
    public function getClassFile(): string
    {
        return $this->path . '/' . ucfirst($this->name) . 'Module.php';
    }

    /**
     * Get the fully qualified class name
     */
    public function getClassName(): string
    {
        $moduleName = ucfirst($this->name);
        return "\\App\\Modules\\{$moduleName}\\{$moduleName}Module";
    }

    /**
     * Check if module has assets for a context
     */
    public function hasAssets(string $context = 'frontend'): bool
    {
        return is_dir($this->path . "/view/{$context}");
    }

    /**
     * Load and return events configuration
     */
    public function loadEvents(): array
    {
        if ($this->eventsFile === null) {
            return [];
        }

        $file = $this->getFilePath($this->eventsFile);
        return file_exists($file) ? (require $file) : [];
    }

    /**
     * Load and return module configuration
     */
    public function loadConfig(): array
    {
        if ($this->configFile === null) {
            return [];
        }

        $file = $this->getFilePath($this->configFile);
        return file_exists($file) ? (require $file) : [];
    }

    /**
     * Load and return routes configuration
     */
    public function loadRoutes(): array
    {
        if ($this->routesFile === null) {
            return [];
        }

        $file = $this->getFilePath($this->routesFile);
        return file_exists($file) ? (require $file) : [];
    }

    /**
     * Convert to array for caching
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'path' => $this->path,
            'dependencies' => $this->dependencies,
            'providers' => $this->providers,
            'commands' => $this->commands,
            'events' => $this->eventsFile,
            'config' => $this->configFile,
            'routes' => $this->routesFile,
            'enabled' => $this->enabled,
        ];
    }

    /**
     * Create from cached array
     */
    public static function fromArray(array $data): self
    {
        return new self($data, $data['path']);
    }
}
