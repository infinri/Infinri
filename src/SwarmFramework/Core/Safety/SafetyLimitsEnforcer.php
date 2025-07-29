<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Safety;

use Infinri\SwarmFramework\Exceptions\SafetyLimitExceededException;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;

/**
 * Safety Limits Enforcer - Enforces .windsurfrules safety constraints
 * 
 * Implements all safety limits from .windsurfrules:
 * - max_unit_execution_time: 30s
 * - max_memory_per_unit: 256MB
 * - max_mesh_keys_per_unit: 1000
 * - max_concurrent_units: 20000
 * - max_mesh_value_size: 1MB
 * 
 * @reference .windsurfrules → SAFETY LIMITS
 * @author Infinri Framework
 * @version 1.0.0
 */
final class SafetyLimitsEnforcer
{
    private const MAX_UNIT_EXECUTION_TIME = 30; // seconds
    private const MAX_MEMORY_PER_UNIT = 268435456; // 256MB in bytes
    private const MAX_MESH_KEYS_PER_UNIT = 1000;
    private const MAX_CONCURRENT_UNITS = 20000;
    private const MAX_MESH_VALUE_SIZE = 1048576; // 1MB in bytes
    private const MAX_RECURSION_DEPTH = 5;
    private const MESH_OPERATION_TIMEOUT = 5; // seconds
    private const UNIT_SPAWN_RATE_LIMIT = 1000; // per second
    private const MAX_BATCH_SIZE = 500;
    private const CONNECTION_POOL_SIZE = 100;

    private array $activeUnits = [];
    private array $unitStartTimes = [];
    private array $unitMemoryUsage = [];
    private int $currentConcurrentUnits = 0;

    /**
     * Check if unit execution can start
     * 
     * @param string $unitId Unit identifier
     * @throws SafetyLimitExceededException If safety limits would be exceeded
     */
    public function checkExecutionStart(string $unitId): void
    {
        // Check concurrent units limit
        if ($this->currentConcurrentUnits >= self::MAX_CONCURRENT_UNITS) {
            throw new SafetyLimitExceededException(
                "Concurrent units limit exceeded: {$this->currentConcurrentUnits}/{" . self::MAX_CONCURRENT_UNITS . "}"
            );
        }

        // Record start time
        $this->unitStartTimes[$unitId] = PerformanceTimer::now();
        $this->activeUnits[$unitId] = true;
        $this->currentConcurrentUnits++;
    }

    /**
     * Check unit execution time limit
     * 
     * @param string $unitId Unit identifier
     * @throws SafetyLimitExceededException If execution time exceeds limit
     */
    public function checkExecutionTime(string $unitId): void
    {
        if (!isset($this->unitStartTimes[$unitId])) {
            return;
        }

        $executionTime = PerformanceTimer::now() - $this->unitStartTimes[$unitId];
        
        if ($executionTime > self::MAX_UNIT_EXECUTION_TIME) {
            throw new SafetyLimitExceededException(
                "Unit execution time limit exceeded: {$executionTime}s > " . self::MAX_UNIT_EXECUTION_TIME . "s"
            );
        }
    }

    /**
     * Check memory usage for unit
     * 
     * @param string $unitId Unit identifier
     * @throws SafetyLimitExceededException If memory usage exceeds limit
     */
    public function checkMemoryUsage(string $unitId): void
    {
        $currentMemory = memory_get_usage(true);
        $unitMemory = $currentMemory - ($this->unitMemoryUsage[$unitId] ?? 0);

        if ($unitMemory > self::MAX_MEMORY_PER_UNIT) {
            throw new SafetyLimitExceededException(
                "Unit memory limit exceeded: " . round($unitMemory / 1048576, 2) . "MB > " . 
                round(self::MAX_MEMORY_PER_UNIT / 1048576, 2) . "MB"
            );
        }
    }

    /**
     * Check mesh keys count for unit
     * 
     * @param array $meshKeys Array of mesh keys accessed by unit
     * @throws SafetyLimitExceededException If mesh keys exceed limit
     */
    public function checkMeshKeysLimit(array $meshKeys): void
    {
        $keyCount = count($meshKeys);
        
        if ($keyCount > self::MAX_MESH_KEYS_PER_UNIT) {
            throw new SafetyLimitExceededException(
                "Mesh keys limit exceeded: {$keyCount} > " . self::MAX_MESH_KEYS_PER_UNIT
            );
        }
    }

    /**
     * Check mesh value size
     * 
     * @param mixed $value Value to check
     * @throws SafetyLimitExceededException If value size exceeds limit
     */
    public function checkMeshValueSize(mixed $value): void
    {
        $serialized = serialize($value);
        $size = strlen($serialized);
        
        if ($size > self::MAX_MESH_VALUE_SIZE) {
            throw new SafetyLimitExceededException(
                "Mesh value size limit exceeded: " . round($size / 1048576, 2) . "MB > " . 
                round(self::MAX_MESH_VALUE_SIZE / 1048576, 2) . "MB"
            );
        }
    }

    /**
     * Check recursion depth
     * 
     * @param int $depth Current recursion depth
     * @throws SafetyLimitExceededException If recursion depth exceeds limit
     */
    public function checkRecursionDepth(int $depth): void
    {
        if ($depth > self::MAX_RECURSION_DEPTH) {
            throw new SafetyLimitExceededException(
                "Recursion depth limit exceeded: {$depth} > " . self::MAX_RECURSION_DEPTH
            );
        }
    }

    /**
     * Record unit completion
     * 
     * @param string $unitId Unit identifier
     */
    public function recordExecutionEnd(string $unitId): void
    {
        unset($this->unitStartTimes[$unitId]);
        unset($this->activeUnits[$unitId]);
        unset($this->unitMemoryUsage[$unitId]);
        
        if ($this->currentConcurrentUnits > 0) {
            $this->currentConcurrentUnits--;
        }
    }

    /**
     * Get current safety metrics
     * 
     * @return array Safety metrics
     */
    public function getSafetyMetrics(): array
    {
        return [
            'concurrent_units' => $this->currentConcurrentUnits,
            'max_concurrent_units' => self::MAX_CONCURRENT_UNITS,
            'active_units' => count($this->activeUnits),
            'max_execution_time' => self::MAX_UNIT_EXECUTION_TIME,
            'max_memory_per_unit_mb' => round(self::MAX_MEMORY_PER_UNIT / 1048576, 2),
            'max_mesh_keys_per_unit' => self::MAX_MESH_KEYS_PER_UNIT,
            'max_mesh_value_size_mb' => round(self::MAX_MESH_VALUE_SIZE / 1048576, 2),
            'max_recursion_depth' => self::MAX_RECURSION_DEPTH
        ];
    }

    /**
     * Emergency brake - force stop all units
     */
    public function emergencyBrake(): void
    {
        $this->activeUnits = [];
        $this->unitStartTimes = [];
        $this->unitMemoryUsage = [];
        $this->currentConcurrentUnits = 0;
    }

    /**
     * Check if system is within safety limits
     * 
     * @return bool True if within limits
     */
    public function isWithinSafetyLimits(): bool
    {
        return $this->currentConcurrentUnits < self::MAX_CONCURRENT_UNITS &&
               memory_get_usage(true) < (self::MAX_MEMORY_PER_UNIT * $this->currentConcurrentUnits);
    }
}
