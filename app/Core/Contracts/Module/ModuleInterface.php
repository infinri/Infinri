<?php

declare(strict_types=1);

namespace App\Core\Contracts\Module;

/**
 * Module Interface
 * 
 * Defines the contract that all platform modules must implement.
 * This establishes clear public-private boundaries for modules
 * and enables safe third-party module integration.
 * 
 * Modules implementing this interface gain:
 * - Automatic registration with ModuleRegistry
 * - Lifecycle hooks (install, upgrade, uninstall)
 * - Dependency injection support
 * - Event subscription capabilities
 */
interface ModuleInterface
{
    /**
     * Get the module's unique identifier
     * 
     * Must be lowercase, alphanumeric with underscores.
     * Example: 'mail', 'user_management', 'payment_stripe'
     */
    public function getName(): string;

    /**
     * Get the module's version
     * 
     * Follows semantic versioning (MAJOR.MINOR.PATCH)
     */
    public function getVersion(): string;

    /**
     * Get module dependencies
     * 
     * Returns array of module names this module depends on.
     * These will be loaded before this module.
     * 
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Register module services
     * 
     * Called during the registration phase before boot.
     * Use this to bind services into the container.
     * 
     * @param \App\Core\Contracts\Container\ContainerInterface $container
     */
    public function register(\App\Core\Contracts\Container\ContainerInterface $container): void;

    /**
     * Boot the module
     * 
     * Called after all modules are registered.
     * Use this to configure services, register routes, etc.
     * 
     * @param \App\Core\Contracts\Container\ContainerInterface $container
     */
    public function boot(\App\Core\Contracts\Container\ContainerInterface $container): void;

    /**
     * Get the module's service providers
     * 
     * @return string[] Array of ServiceProvider class names
     */
    public function getProviders(): array;

    /**
     * Get the module's console commands
     * 
     * @return string[] Array of Command class names
     */
    public function getCommands(): array;

    /**
     * Check if the module is enabled
     */
    public function isEnabled(): bool;
}
