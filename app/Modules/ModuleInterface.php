<?php declare(strict_types=1);

namespace App\Modules;

use Psr\Container\ContainerInterface;

/**
 * Contract for application modules.
 * @see https://github.com/infinri/Infinri/docs/modules/development-guide.md
 */
interface ModuleInterface
{
    /** @return string Unique module identifier (e.g., "vendor/package") */
    public function getId(): string;
    
    /** @return string Human-readable module name */
    public function getName(): string;
    
    /** @return string Semantic version (e.g., "1.0.0") */
    public function getVersion(): string;
    
    /** @return string Brief module description */
    public function getDescription(): string;
    
    /** @return array{name: string, email?: string, url?: string} Author information */
    public function getAuthor(): array;
    
    /** 
     * Register module services and configurations.
     * @throws \RuntimeException On registration failure
     */
    public function register(): void;
    
    /** Bootstrap the module after all modules are registered. */
    public function boot(): void;
    
    /** @return string Absolute path to module root */
    public function getBasePath(): string;
    
    /** @return string Path to module's views directory */
    public function getViewsPath(): string;
    
    /** @return string Module's root namespace */
    public function getNamespace(): string;
    
    /**
     * @return array<class-string,string|\App\Modules\ValueObject\VersionConstraint> Map of module class names to version constraints
     * @example ['App\\Modules\\Core\\CoreModule' => '^1.0']
     */
    public function getDependencies(): array;
    
    /**
     * Get the module's optional dependencies
     * 
     * These modules will be loaded if available, but their absence won't prevent
     * this module from being loaded.
     * 
     * @return array<class-string,string|\App\Modules\ValueObject\VersionConstraint> Map of module class names to version constraints
     */
    public function getOptionalDependencies(): array;
    
    /**
     * Get modules that this module conflicts with
     * 
     * If any of these modules are loaded, this module will not be loaded.
     * 
     * @return array<class-string,string|\App\Modules\ValueObject\VersionConstraint> Map of module class names to version constraints
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
     * @return array<string,string|\App\Modules\ValueObject\VersionConstraint> Map of requirement names to version constraints
     */
    public function getRequirements(): array;
}
