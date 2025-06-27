<?php declare(strict_types=1);

namespace App\Modules;

use App\Modules\ValueObject\ModuleMetadata;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Base module implementation with core functionality.
 * Extended by application modules to provide specific features.
 */
abstract class BaseModule implements ModuleInterface
{
    protected ContainerInterface $container;
    protected ModuleMetadata $metadata;

    /**
     * @throws ReflectionException If class reflection fails
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initialize();
    }

    /**
     * Initialize the module.
     * @throws ReflectionException If class reflection fails
     */
    protected function initialize(): void
    {
        $this->metadata = $this->createMetadata();
    }

    /**
     * Create and configure the module metadata.
     * @throws ReflectionException If class reflection fails
     */
    protected function createMetadata(): ModuleMetadata
    {
        $reflection = new ReflectionClass($this);
        $basePath = dirname($reflection->getFileName(), 2); // Go up two levels from the module class
        
        return new ModuleMetadata(
            id: $this->getId(),
            name: $this->getName(),
            version: $this->getVersion(),
            description: $this->getDescription(),
            author: $this->getAuthor(),
            basePath: $basePath,
            namespace: $this->getNamespace(),
            requirements: $this->getRequirements(),
            dependencies: $this->getDependencies(),
            optionalDependencies: $this->getOptionalDependencies(),
            conflicts: $this->getConflicts(),
            state: ModuleState::UNINSTALLED
        );
    }

    // Default implementations of ModuleInterface methods
    
    public function getId(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        $vendor = strtolower($parts[0] ?? 'app');
        $package = strtolower($parts[2] ?? 'module');
        return "$vendor/$package";
    }

    public function getName(): string
    {
        $className = get_class($this);
        $shortName = (new \ReflectionClass($this))->getShortName();
        return str_replace('Module', '', $shortName) ?: 'Unnamed';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getAuthor(): array
    {
        return [
            'name' => 'Unknown',
            'email' => null,
            'url' => null,
        ];
    }

    public function getBasePath(): string
    {
        return $this->metadata->getBasePath();
    }

    public function getViewsPath(): string
    {
        return $this->getBasePath() . '/Views';
    }

    public function getNamespace(): string
    {
        $className = get_class($this);
        return substr($className, 0, strrpos($className, '\\'));
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getOptionalDependencies(): array
    {
        return [];
    }

    public function getConflicts(): array
    {
        return [];
    }

    public function getRequirements(): array
    {
        return ['php' => '^8.1'];
    }

    public function register(): void
    {
        // Default implementation does nothing
    }

    public function boot(): void
    {
        // Default implementation does nothing
    }

    public function getState(): ModuleState
    {
        return $this->metadata->getState();
    }

    public function setState(ModuleState $state): void
    {
        $this->metadata = $this->metadata->withState($state);
    }

    /**
     * Get the module metadata.
     */
    public function getMetadata(): ModuleMetadata
    {
        return $this->metadata;
    }
}
