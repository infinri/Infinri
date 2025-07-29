<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Interfaces;

/**
 * Semantic Mesh Interface - The Living Memory of Digital Consciousness
 * 
 * The semantic mesh serves as the shared memory space where all SwarmUnits
 * coordinate their activities. It implements the Semantic Mesh Pattern™
 * with O(1) performance guarantees and namespace partitioning.
 * 
 * @performance O(1) mesh operations via Redis Cluster
 * @architecture Implements Semantic Mesh Pattern™
 * @reference infinri_blueprint.md → FR-CORE-002
 * @tactic TAC-PERF-001 (Four-tier caching)
 * @author Infinri Framework
 * @version 1.0.0
 */
interface SemanticMeshInterface
{
    /**
     * Retrieve a value from the semantic mesh
     * 
     * @param string $key The mesh key to retrieve
     * @param string|null $namespace Optional namespace for partitioning (e.g., 'blog', 'user', 'admin')
     * @return mixed The stored value or null if not found
     * @throws MeshAccessException If access is denied by ACL
     * @throws MeshCorruptionException If data integrity check fails
     */
    public function get(string $key, ?string $namespace = null): mixed;

    /**
     * Store a value in the semantic mesh
     * 
     * @param string $key The mesh key to store
     * @param mixed $value The value to store (must be serializable)
     * @param string|null $namespace Optional namespace for partitioning
     * @return bool True if successfully stored, false otherwise
     * @throws MeshAccessException If write access is denied by ACL
     * @throws MeshCapacityException If mesh capacity limits exceeded
     */
    public function set(string $key, mixed $value, ?string $namespace = null): bool;

    /**
     * Atomic compare-and-set operation for mesh coordination
     * 
     * @param string $key The mesh key to update
     * @param mixed $expected The expected current value
     * @param mixed $value The new value to set
     * @return bool True if the value was updated, false if comparison failed
     * @throws MeshAccessException If access is denied by ACL
     */
    public function compareAndSet(string $key, mixed $expected, mixed $value): bool;

    /**
     * Create a consistent snapshot of mesh state for unit evaluation
     * 
     * @param array $keyPatterns Array of key patterns to include (supports wildcards)
     * @return array Immutable snapshot of mesh state
     * @throws MeshSnapshotException If snapshot creation fails
     */
    public function snapshot(array $keyPatterns = ['*']): array;

    /**
     * Get the version number of a mesh key for optimistic locking
     * 
     * @param string $key The mesh key to check
     * @return int The current version number (0 if key doesn't exist)
     */
    public function getVersion(string $key): int;

    /**
     * Subscribe to mesh changes matching a pattern
     * 
     * @param string $pattern Key pattern to watch (supports wildcards)
     * @param callable $callback Function to call when changes occur
     * @return void
     * @throws MeshSubscriptionException If subscription fails
     */
    public function subscribe(string $pattern, callable $callback): void;

    /**
     * Publish an event to mesh subscribers
     * 
     * @param string $channel The channel to publish to
     * @param array $data The event data to publish
     * @return void
     * @throws MeshPublishException If publish fails
     */
    public function publish(string $channel, array $data): void;

    /**
     * Retrieve all mesh keys and values (use with caution)
     * 
     * @return array Complete mesh state
     * @throws MeshAccessException If full access is denied
     */
    public function all(): array;

    /**
     * Check if a key exists in the mesh
     * 
     * @param string $key The mesh key to check
     * @param string|null $namespace Optional namespace
     * @return bool True if key exists, false otherwise
     */
    public function exists(string $key, ?string $namespace = null): bool;

    /**
     * Delete a key from the mesh
     * 
     * @param string $key The mesh key to delete
     * @param string|null $namespace Optional namespace
     * @return bool True if key was deleted, false if it didn't exist
     * @throws MeshAccessException If delete access is denied
     */
    public function delete(string $key, ?string $namespace = null): bool;

    /**
     * Get mesh statistics and health metrics
     * 
     * @return array Statistics including key count, memory usage, hit rates
     */
    public function getStats(): array;

    /**
     * Clear all mesh data (use with extreme caution)
     * 
     * @param string|null $namespace Optional namespace to clear (null clears all)
     * @return bool True if cleared successfully
     * @throws MeshAccessException If clear access is denied
     */
    public function clear(?string $namespace = null): bool;
}
