<?php declare(strict_types=1);

namespace App\Modules;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;

/**
 * Base module class providing common functionality for all application modules.
 * 
 * This abstract class implements ModuleInterface and provides default implementations
 * for common module functionality. Concrete module classes should extend this class
 * and implement the required methods.
 * 
 * @version 2.0.0
 */
abstract class Module implements ModuleInterface
{
    /**
     * The module's unique identifier (e.g., 'vendor/package-name')
     */
    protected string $id;
    
    /**
     * The module's version (e.g., '1.0.0')
     */
    protected string $version = '1.0.0';
    
    /**
     * The module's display name
     */
    protected string $name;
    
    /**
     * The module's description
     */
    protected string $description = '';
    
    /**
     * The module's author information
     */
    protected array $author = [
        'name' => '',
        'email' => null,
        'url' => null,
    ];
    
    /**
     * The module's base path
     */
    protected string $basePath;
    
    /**
     * The application container
     */
    protected ContainerInterface $container;
    
    /**
     * Module requirements
     */
    protected array $requirements = [
        'php' => '^8.1', // Default PHP requirement
    ];
    
    /**
     * Module dependencies
     */
    protected array $dependencies = [];
    
    /**
     * Optional module dependencies
     */
    protected array $optionalDependencies = [];
    
    /**
     * Module conflicts
     */
    protected array $conflicts = [];
    
    /**
     * Module state
     */
    protected ModuleState $state = ModuleState::UNINSTALLED;

    /**
     * Module constructor.
     *
     * @param ContainerInterface $container The application container
     * @throws ReflectionException If the module class cannot be reflected
     * @throws InvalidArgumentException If the module configuration is invalid
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->basePath = $this->getBasePath();
        $this->initializeModule();
        $this->validateModule();
    }
    
    /**
     * Initialize module properties
     */
    protected function initializeModule(): void
    {
        $this->name = $this->getName();
        $this->id = $this->id ?? $this->generateDefaultId();
        $this->version = $this->version;
    }
    
    /**
     * Validate module configuration
     * 
     * @throws InvalidArgumentException If the module configuration is invalid
     */
    protected function validateModule(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException('Module ID cannot be empty');
        }
        
        if (!preg_match('/^[a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*$/', $this->id)) {
            throw new InvalidArgumentException(
                'Module ID must be in the format "vendor/package-name" (lowercase, letters, numbers, and hyphens only)'
            );
        }
        
        try {
            new ModuleVersion($this->version);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('Invalid version string "%s": %s', $this->version, $e->getMessage())
            );
        }
        
        if (empty($this->author['name'])) {
            throw new InvalidArgumentException('Module author name is required');
        }
    }
    
    /**
     * Generate a default module ID based on the class name
     */
    protected function generateDefaultId(): string
    {
        $className = static::class;
        $parts = explode('\\', $className);
        
        // Remove 'App\\Modules' from the beginning if present
        if ($parts[0] === 'App' && isset($parts[1]) && $parts[1] === 'Modules') {
            $parts = array_slice($parts, 2);
        }
        
        // Remove 'Module' suffix if present
        $lastPart = end($parts);
        if (str_ends_with($lastPart, 'Module')) {
            $parts[count($parts) - 1] = substr($lastPart, 0, -6);
        }
        
        $vendor = strtolower($parts[0] ?? 'app');
        $package = strtolower(implode('-', array_slice($parts, 1)) ?: 'module');
        
        return "{$vendor}/{$package}";
    }

    /**
     * Get the module's unique identifier
     * 
     * @return string The module's unique identifier (e.g., 'vendor/package-name')
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the module's display name
     * 
     * @return string The module's display name
     */
    public function getName(): string
    {
        if (!isset($this->name)) {
            $className = static::class;
            $namespaceParts = explode('\\', $className);
            $name = end($namespaceParts);
            
            if (str_ends_with($name, 'Module')) {
                $name = substr($name, 0, -6);
            }
            
            // Convert from CamelCase to Title Case with spaces
            $name = preg_replace('/(?<=\w)(?=[A-Z])/', ' $1', $name);
            $this->name = trim($name);
        }
        
        return $this->name;
    }
    
    /**
     * Get the module's version
     * 
     * @return string The module's version in semantic versioning format
     */
    public function getVersion(): string
    {
        return $this->version;
    }
    
    /**
     * Get the module's description
     * 
     * @return string The module's description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Get the module's author information
     * 
     * @return array{name: string, email?: string, url?: string} The module author's details
     */
    public function getAuthor(): array
    {
        return $this->author;
    }

    /**
     * Get the base path of the module
     *
     * @return string The module's base path
     * @throws ReflectionException If the module class cannot be reflected
     */
    public function getBasePath(): string
    {
        $reflection = new ReflectionClass($this);
        return dirname($reflection->getFileName(), 2);
    }

    /**
     * Get the path to the module's views directory
     *
     * @return string The path to the views directory
     */
    public function getViewsPath(): string
    {
        return $this->basePath . '/Views';
    }

    /**
     * Get the module's namespace
     * 
     * @return string The module's root namespace
     */
    public function getNamespace(): string
    {
        $class = get_class($this);
        return substr($class, 0, strrpos($class, '\\'));
    }
    
    /**
     * Get the module's dependencies
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    /**
     * Get the module's optional dependencies
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getOptionalDependencies(): array
    {
        return $this->optionalDependencies;
    }
    
    /**
     * Get modules that this module conflicts with
     * 
     * @return array<string,string> Map of module class names to version constraints
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }
    
    /**
     * Check if the module is compatible with the current environment
     * 
     * @return bool True if the module can be installed/loaded in the current environment
     */
    public function isCompatible(): bool
    {
        // Check PHP version
        if (isset($this->requirements['php'])) {
            $phpVersion = new ModuleVersion(PHP_VERSION);
            $requiredVersion = $this->requirements['php'];
            
            try {
                if (!$phpVersion->satisfies($requiredVersion)) {
                    return false;
                }
            } catch (InvalidArgumentException $e) {
                // Invalid version constraint, assume incompatible
                return false;
            }
        }
        
        // Check PHP extensions
        foreach ($this->requirements as $requirement => $constraint) {
            if (str_starts_with($requirement, 'ext-')) {
                $extension = substr($requirement, 4);
                if (!extension_loaded($extension)) {
                    return false;
                }
                
                // Check extension version if specified
                if ($constraint !== '*' && extension_loaded($extension)) {
                    $extVersion = phpversion($extension) ?: '0';
                    try {
                        $version = new ModuleVersion($extVersion);
                        if (!$version->satisfies($constraint)) {
                            return false;
                        }
                    } catch (InvalidArgumentException $e) {
                        // Invalid version, assume incompatible
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get the module's requirements
     * 
     * @return array<string,string> Map of requirement names to version constraints
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Register any module services
     * 
     * This method is called during module registration. Implement this method
     * to register any services, controllers, or other resources with the container.
     * 
     * @throws \RuntimeException If the module cannot be registered
     */
    public function register(): void
    {
        // Default implementation does nothing
    }

    /**
     * Boot the module
     * 
     * This method is called after all modules have been registered and all
     * services are available. Use this method to perform any initialization
     * that requires access to other services.
     */
    public function boot(): void
    {
        // Default implementation does nothing
    }
    
    /**
     * Get the module's current state
     */
    public function getState(): ModuleState
    {
        return $this->state;
    }
    
    /**
     * Set the module's state
     * 
     * @internal This method should only be called by the ModuleManager
     */
    public function setState(ModuleState $state): void
    {
        $this->state = $state;
    }
}
