<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Contracts\Container\ContainerInterface;
use App\Core\Contracts\Module\ModuleInterface;

/**
 * Abstract Module
 * 
 * Base class for all platform modules. Provides sensible defaults
 * and common functionality.
 * 
 * Modules can extend this class and override methods as needed.
 * 
 * Example:
 * ```php
 * class ContactModule extends AbstractModule
 * {
 *     protected string $name = 'contact';
 *     protected string $version = '1.0.0';
 *     
 *     protected array $providers = [
 *         ContactServiceProvider::class,
 *     ];
 *     
 *     public function boot(ContainerInterface $container): void
 *     {
 *         // Register routes, event listeners, etc.
 *     }
 * }
 * ```
 */
abstract class AbstractModule implements ModuleInterface
{
    /**
     * Module name (override in subclass)
     */
    protected string $name = '';

    /**
     * Module version
     */
    protected string $version = '1.0.0';

    /**
     * Module description
     */
    protected string $description = '';

    /**
     * Module dependencies
     * @var string[]
     */
    protected array $dependencies = [];

    /**
     * Service providers
     * @var string[]
     */
    protected array $providers = [];

    /**
     * Console commands
     * @var string[]
     */
    protected array $commands = [];

    /**
     * Whether module is enabled
     */
    protected bool $enabled = true;

    /**
     * Module path (auto-detected)
     */
    protected ?string $path = null;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            // Auto-detect from class name: ContactModule -> contact
            $className = (new \ReflectionClass($this))->getShortName();
            $this->name = strtolower(str_replace('Module', '', $className));
        }
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register providers
        foreach ($this->providers as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass($container);
                $provider->register();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Boot providers
        foreach ($this->providers as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass($container);
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get module description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the module's path
     */
    public function getPath(): string
    {
        if ($this->path === null) {
            $reflector = new \ReflectionClass($this);
            $this->path = dirname($reflector->getFileName());
        }
        return $this->path;
    }

    /**
     * Get path to a file within the module
     */
    public function getFilePath(string $relativePath): string
    {
        return $this->getPath() . '/' . ltrim($relativePath, '/');
    }

    /**
     * Check if module has a file
     */
    public function hasFile(string $relativePath): bool
    {
        return file_exists($this->getFilePath($relativePath));
    }

    /**
     * Get the view path for this module
     */
    public function getViewPath(string $area = 'frontend'): string
    {
        return $this->getPath() . "/view/{$area}";
    }

    /**
     * Check if module has views for an area
     */
    public function hasViews(string $area = 'frontend'): bool
    {
        return is_dir($this->getViewPath($area));
    }
}
