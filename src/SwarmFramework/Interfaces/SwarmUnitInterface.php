<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Interfaces;

use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;

/**
 * SwarmUnit Interface - The Conscious Behavior Contract
 * 
 * Every SwarmUnit in the Infinri Framework must implement this interface.
 * SwarmUnits are autonomous agents that react to mesh state changes and
 * perform specific actions within the digital consciousness ecosystem.
 * 
 * @architecture Implements Swarm Unit Pattern™
 * @reference infinri_blueprint.md → FR-CORE-001
 * @reference swarm_pattern_originals_definitions.md → Swarm Unit Pattern™
 * @author Infinri Framework
 * @version 1.0.0
 */
interface SwarmUnitInterface
{
    /**
     * Evaluate whether this unit should trigger based on current mesh state
     * 
     * This method is called during each reactor tick to determine if the unit
     * should execute. It receives a consistent snapshot of the mesh state
     * to ensure deterministic evaluation.
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh snapshot
     * @return bool True if the unit should execute, false otherwise
     * @throws UnitEvaluationException If evaluation fails
     */
    public function triggerCondition(SemanticMeshInterface $mesh): bool;

    /**
     * Execute the unit's primary action
     * 
     * This method is called when triggerCondition returns true. It should
     * perform the unit's specific behavior and update the mesh state as needed.
     * All mesh mutations should be atomic and consistent.
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh for state mutations
     * @return void
     * @throws UnitExecutionException If execution fails
     */
    public function act(SemanticMeshInterface $mesh): void;

    /**
     * Get the unit's identity information
     * 
     * @return UnitIdentity The unit's identity with version, hash, and metadata
     */
    public function getIdentity(): UnitIdentity;

    /**
     * Get the unit's execution priority
     * 
     * Higher priority units execute first when multiple units trigger
     * simultaneously. Priority range: 0 (lowest) to 100 (highest).
     * 
     * @return int Priority level (0-100)
     */
    public function getPriority(): int;

    /**
     * Get the unit's cooldown period in milliseconds
     * 
     * After execution, the unit will not be evaluated again until the
     * cooldown period expires. This prevents excessive execution.
     * 
     * @return int Cooldown period in milliseconds (0 = no cooldown)
     */
    public function getCooldown(): int;

    /**
     * Get the unit's mutex group identifier
     * 
     * Units in the same mutex group cannot execute simultaneously.
     * This prevents resource conflicts and ensures data consistency.
     * 
     * @return string|null Mutex group identifier (null = no mutex)
     */
    public function getMutexGroup(): ?string;

    /**
     * Get the unit's resource requirements
     * 
     * @return array Resource requirements (memory, CPU, I/O limits)
     */
    public function getResourceRequirements(): array;

    /**
     * Get the unit's timeout limit in milliseconds
     * 
     * @return int Maximum execution time (0 = no timeout)
     */
    public function getTimeout(): int;

    /**
     * Check if the unit is currently healthy and ready to execute
     * 
     * @return bool True if healthy, false if degraded or failed
     */
    public function isHealthy(): bool;

    /**
     * Get the unit's health status and metrics
     * 
     * @return array Health metrics including error counts, performance stats
     */
    public function getHealthMetrics(): array;

    /**
     * Handle unit initialization before first execution
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     * @throws UnitInitializationException If initialization fails
     */
    public function initialize(SemanticMeshInterface $mesh): void;

    /**
     * Handle unit cleanup before shutdown
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     */
    public function shutdown(SemanticMeshInterface $mesh): void;

    /**
     * Validate the unit's configuration and dependencies
     * 
     * @return ValidationResult Validation result with any errors or warnings
     */
    public function validate(): ValidationResult;
}
