<?php declare(strict_types=1);

namespace App\Modules;

use InvalidArgumentException;

/**
 * Immutable value object representing module metadata.
 */
final class ModuleMetadata
{
    /**
     * @param string $id Module identifier (e.g., 'vendor/package')
     * @param string $name Human-readable module name
     * @param string $version Semantic version (e.g., '1.0.0')
     * @param string $description Module description
     * @param array{name: string, email?: string|null, url?: string|null} $author Author information
     * @param string $basePath Absolute path to module root
     * @param string $namespace Module's root namespace
     * @param array<string,string> $requirements Module requirements (e.g., ['php' => '^8.1', 'ext-json' => '*'])
     * @param array<class-string,string> $dependencies Required module dependencies
     * @param array<class-string,string> $optionalDependencies Optional module dependencies
     * @param array<class-string,string> $conflicts Modules that conflict with this one
     * @param ModuleState $state Current module state
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $version,
        private string $description,
        private array $author,
        private string $basePath,
        private string $namespace,
        private array $requirements = ['php' => '^8.1'],
        private array $dependencies = [],
        private array $optionalDependencies = [],
        private array $conflicts = [],
        private ModuleState $state = ModuleState::UNINSTALLED,
    ) {
        $this->validate();
    }

    /**
     * Create metadata from a module instance.
     */
    public static function fromModule(ModuleInterface $module): self
    {
        return new self(
            id: $module->getId(),
            name: $module->getName(),
            version: $module->getVersion(),
            description: $module->getDescription(),
            author: $module->getAuthor(),
            basePath: $module->getBasePath(),
            namespace: $module->getNamespace(),
            requirements: $module->getRequirements(),
            dependencies: $module->getDependencies(),
            optionalDependencies: $module->getOptionalDependencies(),
            conflicts: $module->getConflicts(),
            state: $module->getState(),
        );
    }

    /**
     * Update the module state.
     */
    public function withState(ModuleState $state): self
    {
        $new = clone $this;
        $new->state = $state;
        return $new;
    }

    /**
     * Validate the metadata.
     *
     * @throws InvalidArgumentException If metadata is invalid
     */
    private function validate(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException('Module ID cannot be empty');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*$/', $this->id)) {
            throw new InvalidArgumentException(
                'Module ID must be in the format "vendor/package-name" (lowercase, letters, numbers, and hyphens only)'
            );
        }

        if (empty($this->author['name'])) {
            throw new InvalidArgumentException('Module author name is required');
        }
    }

    // Getters

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }
    public function getDescription(): string { return $this->description; }
    public function getAuthor(): array { return $this->author; }
    public function getBasePath(): string { return $this->basePath; }
    public function getViewsPath(): string { return $this->basePath . '/resources/views'; }
    public function getNamespace(): string { return $this->namespace; }
    public function getState(): ModuleState { return $this->state; }
    
    /** @return array<string,string> */
    public function getRequirements(): array { return $this->requirements; }
    
    /** @return array<class-string,string> */
    public function getDependencies(): array { return $this->dependencies; }
    
    /** @return array<class-string,string> */
    public function getOptionalDependencies(): array { return $this->optionalDependencies; }
    
    /** @return array<class-string,string> */
    public function getConflicts(): array { return $this->conflicts; }
    
    /**
     * Set the module dependencies.
     * 
     * @param array<string,string> $dependencies
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }
    
    /**
     * Set the module conflicts.
     * 
     * @param array<string,string> $conflicts
     */
    public function setConflicts(array $conflicts): void
    {
        $this->conflicts = $conflicts;
    }
    
    /**
     * Convert the metadata to an array.
     * 
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'basePath' => $this->basePath,
            'namespace' => $this->namespace,
            'requirements' => $this->requirements,
            'dependencies' => $this->dependencies,
            'optionalDependencies' => $this->optionalDependencies,
            'conflicts' => $this->conflicts,
            'state' => $this->state->value,
        ];
    }
}
