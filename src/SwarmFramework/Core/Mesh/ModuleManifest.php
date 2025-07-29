<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Mesh;

use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * Module Manifest - Module Metadata and Configuration
 * 
 * Represents the metadata and configuration for a Swarm module,
 * including units, dependencies, capabilities, and API endpoints.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
final class ModuleManifest
{
    private string $name;
    private string $version;
    private string $description;
    private array $units;
    private array $dependencies;
    private array $capabilities;
    private array $apiEndpoints;
    private array $adminInterfaces;
    private array $metadata;

    public function __construct(
        string $name,
        string $version,
        string $description = '',
        array $units = [],
        array $dependencies = [],
        array $capabilities = [],
        array $apiEndpoints = [],
        array $adminInterfaces = [],
        array $metadata = []
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->description = $description;
        $this->units = $units;
        $this->dependencies = $dependencies;
        $this->capabilities = $capabilities;
        $this->apiEndpoints = $apiEndpoints;
        $this->adminInterfaces = $adminInterfaces;
        $this->metadata = $metadata;
    }

    /**
     * Create manifest from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            version: $data['version'] ?? '',
            description: $data['description'] ?? '',
            units: $data['units'] ?? [],
            dependencies: $data['dependencies'] ?? [],
            capabilities: $data['capabilities'] ?? [],
            apiEndpoints: $data['api_endpoints'] ?? [],
            adminInterfaces: $data['admin_interfaces'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert manifest to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'units' => $this->units,
            'dependencies' => $this->dependencies,
            'capabilities' => $this->capabilities,
            'api_endpoints' => $this->apiEndpoints,
            'admin_interfaces' => $this->adminInterfaces,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Create manifest from JSON file
     */
    public static function fromJsonFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw ExceptionFactory::runtime(
                "Manifest file not found: {$filePath}",
                ['file_path' => $filePath]
            );
        }

        try {
            $data = json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);
            return self::fromArray($data);
        } catch (\JsonException $e) {
            throw ExceptionFactory::validation(
                "Invalid JSON in manifest file: {$e->getMessage()}",
                ['file_path' => $filePath, 'json_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Save manifest to JSON file
     */
    public function saveToJsonFile(string $filePath): void
    {
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        file_put_contents($filePath, $json);
    }

    /**
     * Check if manifest has required fields
     */
    public function isValid(): bool
    {
        return !empty($this->name) && !empty($this->version);
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Module name is required';
        }
        
        if (empty($this->version)) {
            $errors[] = 'Module version is required';
        }
        
        if (!preg_match('/^\d+\.\d+\.\d+/', $this->version)) {
            $errors[] = 'Module version must follow semantic versioning (e.g., 1.0.0)';
        }
        
        return $errors;
    }

    /**
     * Check if this manifest is compatible with another version
     */
    public function isCompatibleWith(string $otherVersion): bool
    {
        $thisParts = explode('.', $this->version);
        $otherParts = explode('.', $otherVersion);

        // Major version must match for compatibility
        return $thisParts[0] === $otherParts[0];
    }

    /**
     * Add a unit to the manifest
     */
    public function addUnit(string $unitClass): void
    {
        if (!in_array($unitClass, $this->units)) {
            $this->units[] = $unitClass;
        }
    }

    /**
     * Remove a unit from the manifest
     */
    public function removeUnit(string $unitClass): void
    {
        $this->units = array_filter($this->units, fn($unit) => $unit !== $unitClass);
    }

    /**
     * Check if manifest has a specific unit
     */
    public function hasUnit(string $unitClass): bool
    {
        return in_array($unitClass, $this->units);
    }

    /**
     * Add a dependency
     */
    public function addDependency(string $moduleName, string $versionConstraint): void
    {
        $this->dependencies[$moduleName] = $versionConstraint;
    }

    /**
     * Remove a dependency
     */
    public function removeDependency(string $moduleName): void
    {
        unset($this->dependencies[$moduleName]);
    }

    /**
     * Check if manifest has a specific dependency
     */
    public function hasDependency(string $moduleName): bool
    {
        return isset($this->dependencies[$moduleName]);
    }

    /**
     * Get dependency version constraint
     */
    public function getDependencyConstraint(string $moduleName): ?string
    {
        return $this->dependencies[$moduleName] ?? null;
    }

    // Getters
    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }
    public function getDescription(): string { return $this->description; }
    public function getUnits(): array { return $this->units; }
    public function getDependencies(): array { return $this->dependencies; }
    public function getCapabilities(): array { return $this->capabilities; }
    public function getApiEndpoints(): array { return $this->apiEndpoints; }
    public function getAdminInterfaces(): array { return $this->adminInterfaces; }
    public function getMetadata(): array { return $this->metadata; }

    // Setters
    public function setDescription(string $description): void { $this->description = $description; }
    public function setCapabilities(array $capabilities): void { $this->capabilities = $capabilities; }
    public function setApiEndpoints(array $apiEndpoints): void { $this->apiEndpoints = $apiEndpoints; }
    public function setAdminInterfaces(array $adminInterfaces): void { $this->adminInterfaces = $adminInterfaces; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
}
