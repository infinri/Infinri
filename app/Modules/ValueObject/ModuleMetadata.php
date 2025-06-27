<?php declare(strict_types=1);

namespace App\Modules\ValueObject;

use App\Modules\ModuleInterface;
use App\Modules\ModuleState;
use App\Modules\ValueObject\VersionConstraint;
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
     * @param array<class-string,string|VersionConstraint> $dependencies Required module dependencies
     * @param array<class-string,string|VersionConstraint> $optionalDependencies Optional module dependencies
     * @param array<class-string,string|VersionConstraint> $conflicts Modules that conflict with this one
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
        $this->dependencies = $this->normaliseConstraints($this->dependencies);
        $this->optionalDependencies = $this->normaliseConstraints($this->optionalDependencies);
        $this->conflicts = $this->normaliseConstraints($this->conflicts);

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
    public function getNamespace(): string { return $this->namespace; }
    public function getRequirements(): array { return $this->requirements; }
    /** @return array<class-string,VersionConstraint> */
    public function getDependencies(): array { return $this->dependencies; }
    /** @return array<class-string,VersionConstraint> */
    public function getOptionalDependencies(): array { return $this->optionalDependencies; }
    /** @return array<class-string,VersionConstraint> */
    public function getConflicts(): array { return $this->conflicts; }
    public function getState(): ModuleState { return $this->state; }
    
    /**
     * Get all dependencies (required and optional).
     *
     * @return array<class-string,string> Module class FQCN => version constraint
     */
    /** @return array<class-string,VersionConstraint> */
    public function getAllDependencies(): array
    {
        return array_merge($this->dependencies, $this->optionalDependencies);
    }

    /**
     * Convert all constraint strings to VersionConstraint objects.
     *
     * @param array<class-string,string|VersionConstraint> $map
     * @return array<class-string,VersionConstraint>
     */
    private function normaliseConstraints(array $map): array
    {
        foreach ($map as $moduleClass => $constraint) {
            if (!$constraint instanceof VersionConstraint) {
                $map[$moduleClass] = new VersionConstraint((string)$constraint);
            }
        }
        return $map;
    }
}
