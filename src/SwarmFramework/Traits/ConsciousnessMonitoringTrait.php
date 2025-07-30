<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

/**
 * Consciousness Monitoring Trait
 * 
 * Provides consciousness-level monitoring and health tracking capabilities.
 * Consolidates health metrics, execution recording, and resource monitoring.
 * 
 * @architecture Consciousness monitoring and health tracking
 * @reference infinri_blueprint.md → FR-CORE-028 (Health Monitoring)
 * @author Infinri Framework
 * @version 1.0.0
 */
trait ConsciousnessMonitoringTrait
{
    private array $executionHistory = [];
    private array $healthMetrics = [];
    private float $lastExecutionTime = 0.0;

    /**
     * Initialize health metrics tracking
     * 
     * @return void
     */
    protected function initializeHealthMetrics(): void
    {
        $this->healthMetrics = [
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'total_mutations' => 0,
            'average_execution_time' => 0.0,
            'error_rate' => 0.0,
            'efficiency_score' => 1.0,
            'last_health_check' => microtime(true),
            'resource_usage' => []
        ];
    }

    /**
     * Record execution with comprehensive metrics
     * Implements digital consciousness self-awareness
     * 
     * @param string $status Execution status
     * @param array $metrics Execution metrics
     * @return void
     */
    protected function recordExecution(string $status, array $metrics = []): void
    {
        $timestamp = microtime(true);
        
        $executionRecord = [
            'status' => $status,
            'timestamp' => $timestamp,
            'metrics' => $metrics,
            'unit_id' => $this->getIdentity()->id ?? 'unknown',
            'execution_time' => $timestamp - $this->lastExecutionTime
        ];
        
        $this->executionHistory[] = $executionRecord;
        $this->lastExecutionTime = $timestamp;
        
        // Update health metrics
        $this->updateHealthMetrics(
            $executionRecord['execution_time'],
            $metrics['mutations'] ?? 0,
            $status === 'failed' || $status === 'error'
        );
        
        // Limit execution history size for memory management
        if (count($this->executionHistory) > 100) {
            array_shift($this->executionHistory);
        }
    }

    /**
     * Get current resource usage for consciousness monitoring
     * 
     * @return array Resource usage metrics
     */
    protected function getResourceUsage(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'execution_count' => $this->healthMetrics['total_executions'],
            'cpu_time' => getrusage()['ru_utime.tv_sec'] ?? 0,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Update health metrics with execution data
     * 
     * @param float $executionTime Execution time
     * @param int $mutations Number of mutations
     * @param bool $failed Whether execution failed
     * @return void
     */
    private function updateHealthMetrics(float $executionTime, int $mutations, bool $failed = false): void
    {
        $this->healthMetrics['total_executions']++;
        $this->healthMetrics['total_mutations'] += $mutations;
        
        if ($failed) {
            $this->healthMetrics['failed_executions']++;
        } else {
            $this->healthMetrics['successful_executions']++;
        }
        
        // Update average execution time
        $totalExecs = $this->healthMetrics['total_executions'];
        $currentAvg = $this->healthMetrics['average_execution_time'];
        $this->healthMetrics['average_execution_time'] = 
            (($currentAvg * ($totalExecs - 1)) + $executionTime) / $totalExecs;
        
        // Update error rate
        $this->healthMetrics['error_rate'] = 
            $this->healthMetrics['failed_executions'] / $totalExecs;
        
        // Update efficiency score
        $this->healthMetrics['efficiency_score'] = 
            1.0 - $this->healthMetrics['error_rate'];
        
        $this->healthMetrics['last_health_check'] = microtime(true);
        $this->healthMetrics['resource_usage'] = $this->getResourceUsage();
    }

    /**
     * Check if the unit is currently healthy and ready to execute
     * 
     * @return bool True if healthy, false if degraded or failed
     */
    public function isHealthy(): bool
    {
        return $this->healthMetrics['efficiency_score'] > 0.7 
            && $this->healthMetrics['error_rate'] < 0.3;
    }

    /**
     * Get the unit's health status and metrics
     * 
     * @return array Health metrics including error counts, performance stats
     */
    public function getHealthMetrics(): array
    {
        return $this->healthMetrics;
    }

    /**
     * Calculate mesh state hash for integrity checking
     * 
     * @param array $context Context data
     * @return string Hash of mesh state
     */
    protected function calculateMeshHash(array $context): string
    {
        ksort($context);
        return hash('sha256', serialize($context));
    }
}
