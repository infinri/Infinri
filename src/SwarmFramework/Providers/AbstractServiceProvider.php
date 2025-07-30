<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Providers;

use Infinri\SwarmFramework\DI\SwarmContainer;

/**
 * AbstractServiceProvider - Base Service Provider
 * 
 * Base class for all service providers in the Infinri Framework.
 * Provides common functionality for service registration and lifecycle management.
 * 
 * @architecture Base service provider with lifecycle management
 * @reference infinri_blueprint.md → FR-CORE-019 (Dependency Injection)
 * @author Infinri Framework
 * @version 1.0.0
 */
abstract class AbstractServiceProvider
{
    protected SwarmContainer $container;
    protected bool $registered = false;
    protected bool $booted = false;

    /**
     * Initialize service provider
     * 
     * @param SwarmContainer $container Dependency injection container
     */
    public function __construct(SwarmContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Register services in the container
     * 
     * @return void
     */
    abstract public function register(): void;

    /**
     * Boot services after registration (optional)
     * 
     * @return void
     */
    public function boot(): void
    {
        // Default implementation - override in subclasses if needed
    }

    /**
     * Check if provider has been registered
     * 
     * @return bool True if registered
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Check if provider has been booted
     * 
     * @return bool True if booted
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Mark provider as registered
     * 
     * @return void
     */
    public function markAsRegistered(): void
    {
        $this->registered = true;
    }

    /**
     * Mark provider as booted
     * 
     * @return void
     */
    public function markAsBooted(): void
    {
        $this->booted = true;
    }

    /**
     * Get the container instance
     * 
     * @return SwarmContainer Container instance
     */
    protected function getContainer(): SwarmContainer
    {
        return $this->container;
    }
}
