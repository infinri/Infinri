<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

/**
 * Throttling Manager - Adaptive Unit Execution Control
 * 
 * Manages SwarmUnit execution throttling with adaptive algorithms
 * to prevent system overload and maintain optimal performance.
 * 
 * @architecture Adaptive throttling and execution control
 * @author Infinri Framework
 * @version 1.0.0
 */
final class ThrottlingManager
{
    private array $config;
    private array $executionMetrics = [];
    private float $currentLoad = 0.0;
    private int $activeUnits = 0;

    /**
     * Initialize throttling manager
     * 
     * @param array $config Throttling configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_units_per_tick' => 20000,
            'adaptive_throttling' => true,
            'throttle_threshold' => 0.8,
            'recovery_factor' => 0.9,
            'emergency_brake_threshold' => 0.95
        ], $config);
    }

    /**
     * Check if unit execution should be throttled
     * 
     * @param string $unitId Unit identifier
     * @return bool True if execution should be throttled
     */
    public function shouldThrottle(string $unitId): bool
    {
        $this->updateCurrentLoad();
        
        if ($this->currentLoad > $this->config['emergency_brake_threshold']) {
            return true; // Emergency brake
        }
        
        if ($this->activeUnits >= $this->config['max_units_per_tick']) {
            return true; // Hard limit reached
        }
        
        if ($this->config['adaptive_throttling'] && 
            $this->currentLoad > $this->config['throttle_threshold']) {
            return $this->shouldAdaptiveThrottle($unitId);
        }
        
        return false;
    }

    /**
     * Register unit execution start
     * 
     * @param string $unitId Unit identifier
     * @return void
     */
    public function registerExecution(string $unitId): void
    {
        $this->activeUnits++;
        $this->executionMetrics[$unitId] = [
            'start_time' => microtime(true),
            'status' => 'running'
        ];
    }

    /**
     * Register unit execution completion
     * 
     * @param string $unitId Unit identifier
     * @param bool $success Whether execution was successful
     * @return void
     */
    public function registerCompletion(string $unitId, bool $success = true): void
    {
        $this->activeUnits = max(0, $this->activeUnits - 1);
        
        if (isset($this->executionMetrics[$unitId])) {
            $this->executionMetrics[$unitId]['end_time'] = microtime(true);
            $this->executionMetrics[$unitId]['duration'] = 
                $this->executionMetrics[$unitId]['end_time'] - 
                $this->executionMetrics[$unitId]['start_time'];
            $this->executionMetrics[$unitId]['success'] = $success;
            $this->executionMetrics[$unitId]['status'] = 'completed';
        }
        
        $this->cleanupOldMetrics();
    }

    /**
     * Get current system load
     * 
     * @return float Current load (0.0 to 1.0)
     */
    public function getCurrentLoad(): float
    {
        return $this->currentLoad;
    }

    /**
     * Get active unit count
     * 
     * @return int Number of currently active units
     */
    public function getActiveUnitCount(): int
    {
        return $this->activeUnits;
    }

    /**
     * Get throttling statistics
     * 
     * @return array Throttling statistics
     */
    public function getStatistics(): array
    {
        $totalExecutions = count($this->executionMetrics);
        $successfulExecutions = count(array_filter(
            $this->executionMetrics, 
            fn($metric) => $metric['success'] ?? false
        ));
        
        return [
            'current_load' => $this->currentLoad,
            'active_units' => $this->activeUnits,
            'max_units_per_tick' => $this->config['max_units_per_tick'],
            'total_executions' => $totalExecutions,
            'successful_executions' => $successfulExecutions,
            'success_rate' => $totalExecutions > 0 ? $successfulExecutions / $totalExecutions : 1.0,
            'throttle_threshold' => $this->config['throttle_threshold'],
            'emergency_brake_threshold' => $this->config['emergency_brake_threshold']
        ];
    }

    /**
     * Update current system load
     * 
     * @return void
     */
    private function updateCurrentLoad(): void
    {
        $maxUnits = $this->config['max_units_per_tick'];
        $this->currentLoad = min(1.0, $this->activeUnits / $maxUnits);
        
        // Factor in system resources if available
        if (function_exists('sys_getloadavg')) {
            $systemLoad = sys_getloadavg()[0] ?? 0;
            $cpuCores = $this->getCpuCoreCount();
            $systemLoadRatio = $cpuCores > 0 ? min(1.0, $systemLoad / $cpuCores) : 0;
            
            // Combine unit load and system load
            $this->currentLoad = max($this->currentLoad, $systemLoadRatio);
        }
    }

    /**
     * Determine if adaptive throttling should be applied
     * 
     * @param string $unitId Unit identifier
     * @return bool True if should throttle
     */
    private function shouldAdaptiveThrottle(string $unitId): bool
    {
        // Calculate recent success rate
        $recentMetrics = array_slice($this->executionMetrics, -100);
        $recentSuccesses = count(array_filter(
            $recentMetrics, 
            fn($metric) => $metric['success'] ?? false
        ));
        $recentSuccessRate = count($recentMetrics) > 0 ? 
            $recentSuccesses / count($recentMetrics) : 1.0;
        
        // Throttle more aggressively if success rate is low
        $adaptiveThreshold = $this->config['throttle_threshold'] * $recentSuccessRate;
        
        return $this->currentLoad > $adaptiveThreshold;
    }

    /**
     * Get CPU core count
     * 
     * @return int Number of CPU cores
     */
    private function getCpuCoreCount(): int
    {
        if (function_exists('shell_exec')) {
            $cores = shell_exec('nproc');
            return $cores ? (int)trim($cores) : 1;
        }
        
        return 1; // Default fallback
    }

    /**
     * Clean up old execution metrics
     * 
     * @return void
     */
    private function cleanupOldMetrics(): void
    {
        $cutoffTime = microtime(true) - 300; // 5 minutes ago
        
        $this->executionMetrics = array_filter(
            $this->executionMetrics,
            fn($metric) => ($metric['start_time'] ?? 0) > $cutoffTime
        );
        
        // Limit total metrics stored
        if (count($this->executionMetrics) > 10000) {
            $this->executionMetrics = array_slice($this->executionMetrics, -5000);
        }
    }
}
