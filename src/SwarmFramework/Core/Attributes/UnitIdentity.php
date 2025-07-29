<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Attributes;

use Infinri\SwarmFramework\Exceptions\InvalidUnitIdentityException;

/**
 * SwarmUnit Identity System - Digital DNA for Conscious Units
 * 
 * Provides cryptographic integrity and versioning for SwarmUnits.
 * Each unit has a unique identity with version tracking, capability
 * declarations, and dependency management.
 * 
 * @architecture Unit versioning with cryptographic integrity
 * @reference infinri_blueprint.md → FR-CORE-007
 * @author Infinri Framework
 * @version 1.0.0
 */
final class UnitIdentity
{
    /**
     * @param string $id Unique unit identifier (e.g., 'create-post-v2')
     * @param string $version Semantic version (e.g., '2.1.0')
     * @param string $hash Cryptographic hash of unit implementation
     * @param array $capabilities List of capabilities this unit provides
     * @param array $dependencies List of required dependencies
     * @param array $meshKeys List of mesh keys this unit reads/writes
     * @param string|null $description Human-readable description
     * @param array $metadata Additional metadata for the unit
     */
    public function __construct(
        public readonly string $id,
        public readonly string $version,
        public readonly string $hash,
        public readonly array $capabilities = [],
        public readonly array $dependencies = [],
        public readonly array $meshKeys = [],
        public readonly ?string $description = null,
        public readonly array $metadata = []
    ) {
        $this->validateIdentity();
    }

    /**
     * Validate the identity structure and constraints
     * 
     * @throws InvalidUnitIdentityException If identity is malformed
     */
    private function validateIdentity(): void
    {
        if (empty($this->id)) {
            throw new InvalidUnitIdentityException('Unit ID cannot be empty');
        }

        if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9]+)?$/', $this->version)) {
            throw new InvalidUnitIdentityException('Version must follow semantic versioning');
        }

        if (!preg_match('/^sha256:[a-f0-9]{64}$/', $this->hash)) {
            throw new InvalidUnitIdentityException('Hash must be SHA-256 format');
        }
    }

    /**
     * Check if this unit is compatible with another unit's requirements
     * 
     * @param UnitIdentity $other The other unit to check compatibility with
     * @return bool True if compatible, false otherwise
     */
    public function isCompatibleWith(UnitIdentity $other): bool
    {
        // Check if our capabilities satisfy their dependencies
        foreach ($other->dependencies as $dependency) {
            if (!in_array($dependency, $this->capabilities, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the major version number
     * 
     * @return int Major version
     */
    public function getMajorVersion(): int
    {
        return (int) explode('.', $this->version)[0];
    }

    /**
     * Get the minor version number
     * 
     * @return int Minor version
     */
    public function getMinorVersion(): int
    {
        return (int) explode('.', $this->version)[1];
    }

    /**
     * Get the patch version number
     * 
     * @return int Patch version
     */
    public function getPatchVersion(): int
    {
        $parts = explode('.', $this->version);
        return (int) explode('-', $parts[2])[0];
    }

    /**
     * Check if this is a pre-release version
     * 
     * @return bool True if pre-release, false otherwise
     */
    public function isPreRelease(): bool
    {
        return str_contains($this->version, '-');
    }

    /**
     * Convert identity to array representation
     * 
     * @return array Identity as associative array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'hash' => $this->hash,
            'capabilities' => $this->capabilities,
            'dependencies' => $this->dependencies,
            'meshKeys' => $this->meshKeys,
            'description' => $this->description,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Create identity from array representation
     * 
     * @param array $data Identity data
     * @return UnitIdentity New identity instance
     */
    public static function fromArray(array $data): UnitIdentity
    {
        return new self(
            id: $data['id'],
            version: $data['version'],
            hash: $data['hash'],
            capabilities: $data['capabilities'] ?? [],
            dependencies: $data['dependencies'] ?? [],
            meshKeys: $data['meshKeys'] ?? [],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
}
