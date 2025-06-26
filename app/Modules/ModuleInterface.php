<?php declare(strict_types=1);

namespace App\Modules;

use Psr\Container\ContainerInterface;

/**
 * Defines the contract that all modules must implement.
 * 
 * Modules are the building blocks of the application, providing self-contained
 * functionality that can be enabled, disabled, and managed independently.
 * 
 * @version 2.0.0
 */
interface ModuleInterface
{
    /**
     * Get the module's unique identifier
     * 
     * This should be a unique string that identifies the module, typically
     * in the format "vendor/package-name".
     * 
     * @return string The module's unique identifier
     */
    public function getId(): string;
    
    /**
     * Get the module's display name
     * 
     * @return string A human-readable name for the module
     */
    public function getName(): string;
    
    /**
     * Get the module's version
     * 
     * @return string The module's version in semantic versioning format (e.g., "1.0.0")
     */
    public function getVersion(): string;
    
    /**
     * Get the module's description
     * 
     * @return string A brief description of what the module does
     */
    public function getDescription(): string;
    
    /**
     * Get the module's author information
     * 
     * @return array{name: string, email?: string, url?: string} The module author's details
     */
    public function getAuthor(): array;
    
    /**
     * Register module services and configurations
     * 
     * This method is called when the module is first loaded and should be used
     * to register any services, controllers, or other resources with the container.
     * 
     * @throws \RuntimeException If the module cannot be registered
     */
    public function register(): void;
    
    /**
     * Bootstrap the module
     * 
     * This method is called after all modules have been registered and can be
     * used to perform any initialization that requires access to other services.
     */
    public function boot(): void;
    
    /**
     * Get the module's base path
     * 
     * @return string The absolute path to the module's root directory
     */
    public function getBasePath(): string;
    
    /**
     * Get the path to the module's views directory
     * 
     * @return string The path to the module's views directory
     */
    public function getViewsPath(): string;
    
    /**
     * Get the module's namespace
     * 
     * @return string The module's root namespace
     */
    public function getNamespace(): string;
    
    /**
     * Get the module's dependencies
     * 
     * Return an array where keys are module class names and values are version constraints.
     * Example: [
     *     'App\\Modules\\Core\\CoreModule' => '^1.0',
     *     'App\\Modules\\Auth\\AuthModule' => '>=2.0 <3.0',
     * ]
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getDependencies(): array;
    
    /**
     * Get the module's optional dependencies
     * 
     * These modules will be loaded if available, but their absence won't prevent
     * this module from being loaded.
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getOptionalDependencies(): array;
    
    /**
     * Get modules that this module conflicts with
     * 
     * If any of these modules are loaded, this module will not be loaded.
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getConflicts(): array;
    
    /**
     * Check if the module is compatible with the current environment
     * 
     * @return bool True if the module can be installed/loaded in the current environment
     */
    public function isCompatible(): bool;
    
    /**
     * Get the module's requirements
     * 
     * Return an array of requirements that must be met for this module to function.
     * Example: [
     *     'php' => '^8.1',
     *     'ext-json' => '*',
     *     'ext-pdo' => '>=1.0',
     * ]
     * 
     * @return array<string,string> Map of requirement names to version constraints
     */
    public function getRequirements(): array;
}
