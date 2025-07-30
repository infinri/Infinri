<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Monitoring;

use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;

/**
 * Entropy Monitor - System Entropy and Health Tracking
 * 
 * Monitors system entropy levels and provides auto-pruning capabilities
 * for maintaining optimal system health and performance.
 * 
 * @architecture Entropy monitoring and health tracking
 * @reference infinri_blueprint.md → FR-CORE-028 (Health Monitoring)
 * @reference infinri_blueprint.md → TAC-ENTROPY-001 (Entropy monitoring with auto-pruning)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class EntropyMonitor
{
    private SemanticMeshInterface $mesh;
    private array $config;
    private array $healthMetrics = [];

    /**
     * Initialize entropy monitor
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @param array $config Entropy configuration
     */
    public function __construct(SemanticMeshInterface $mesh, array $config = [])
    {
        $this->mesh = $mesh;
        $this->config = array_merge([
            'max_entropy_threshold' => 0.8,
            'auto_pruning_enabled' => true,
            'monitoring_interval' => 60,
            'health_check_interval' => 300,
            'degradation_threshold' => 0.6,
            'critical_threshold' => 0.9
        ], $config);
        
        $this->initializeHealthMetrics();
    }

    /**
     * Record execution for entropy tracking
     * 
     * @param string $unitId Unit identifier
     * @param string $status Execution status
     * @param array $metrics Execution metrics
     * @return void
     */
    public function recordExecution(string $unitId, string $status, array $metrics): void
    {
        $timestamp = microtime(true);
        
        $this->mesh->set("entropy:executions:{$unitId}:{$timestamp}", [
            'unit_id' => $unitId,
            'status' => $status,
            'metrics' => $metrics,
            'timestamp' => $timestamp
        ]);
        
        $this->updateHealthMetrics($unitId, $status, $metrics);
        $this->checkEntropyLevels();
    }

    /**
     * Get current entropy level
     * 
     * @return float Current entropy level (0.0 to 1.0)
     */
    public function getCurrentEntropyLevel(): float
    {
        $totalExecutions = $this->healthMetrics['total_executions'] ?? 1;
        $failedExecutions = $this->healthMetrics['failed_executions'] ?? 0;
        
        return min(1.0, $failedExecutions / $totalExecutions);
    }

    /**
     * Check if auto-pruning is needed
     * 
     * @return bool True if pruning is needed
     */
    public function needsPruning(): bool
    {
        return $this->getCurrentEntropyLevel() > $this->config['max_entropy_threshold'];
    }

    /**
     * Perform auto-pruning if enabled and needed
     * 
     * @return bool True if pruning was performed
     */
    public function performAutoPruning(): bool
    {
        if (!$this->config['auto_pruning_enabled'] || !$this->needsPruning()) {
            return false;
        }
        
        $this->pruneOldExecutions();
        $this->resetHealthMetrics();
        
        return true;
    }

    /**
     * Get health metrics
     * 
     * @return array Health metrics
     */
    public function getHealthMetrics(): array
    {
        return $this->healthMetrics;
    }

    /**
     * Initialize health metrics
     * 
     * @return void
     */
    private function initializeHealthMetrics(): void
    {
        $this->healthMetrics = [
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'entropy_level' => 0.0,
            'last_check' => microtime(true),
            'pruning_count' => 0
        ];
    }

    /**
     * Update health metrics
     * 
     * @param string $unitId Unit identifier
     * @param string $status Execution status
     * @param array $metrics Execution metrics
     * @return void
     */
    private function updateHealthMetrics(string $unitId, string $status, array $metrics): void
    {
        $this->healthMetrics['total_executions']++;
        
        if ($status === 'success' || $status === 'completed') {
            $this->healthMetrics['successful_executions']++;
        } else {
            $this->healthMetrics['failed_executions']++;
        }
        
        $this->healthMetrics['entropy_level'] = $this->getCurrentEntropyLevel();
        $this->healthMetrics['last_check'] = microtime(true);
    }

    /**
     * Check entropy levels and trigger actions if needed
     * 
     * @return void
     */
    private function checkEntropyLevels(): void
    {
        $entropyLevel = $this->getCurrentEntropyLevel();
        
        if ($entropyLevel > $this->config['critical_threshold']) {
            $this->triggerCriticalAlert();
        } elseif ($entropyLevel > $this->config['max_entropy_threshold']) {
            $this->performAutoPruning();
        }
    }

    /**
     * Prune old execution records
     * 
     * @return void
     */
    private function pruneOldExecutions(): void
    {
        $cutoffTime = microtime(true) - 3600; // 1 hour ago
        $keys = $this->mesh->getKeysByPattern('entropy:executions:*');
        
        foreach ($keys as $key) {
            $data = $this->mesh->get($key);
            if ($data && isset($data['timestamp']) && $data['timestamp'] < $cutoffTime) {
                $this->mesh->delete($key);
            }
        }
        
        $this->healthMetrics['pruning_count']++;
    }

    /**
     * Reset health metrics after pruning
     * 
     * @return void
     */
    private function resetHealthMetrics(): void
    {
        $this->healthMetrics['total_executions'] = 0;
        $this->healthMetrics['successful_executions'] = 0;
        $this->healthMetrics['failed_executions'] = 0;
        $this->healthMetrics['entropy_level'] = 0.0;
    }

    /**
     * Trigger critical entropy alert
     * 
     * @return void
     */
    private function triggerCriticalAlert(): void
    {
        $this->mesh->publish('system:alerts:entropy:critical', [
            'entropy_level' => $this->getCurrentEntropyLevel(),
            'health_metrics' => $this->healthMetrics,
            'timestamp' => microtime(true)
        ]);
    }

    /**
     * Record a metric for entropy tracking
     * 
     * @param string $metricName Metric name
     * @param mixed $value Metric value
     * @param array $context Additional context
     * @return void
     */
    public function recordMetric(string $metricName, mixed $value, array $context = []): void
    {
        $timestamp = microtime(true);
        $this->mesh->set("entropy:metrics:{$metricName}:{$timestamp}", [
            'name' => $metricName,
            'value' => $value,
            'context' => $context,
            'timestamp' => $timestamp
        ]);
    }

    /**
     * Get unit efficacy score
     * 
     * @param string $unitId Unit identifier
     * @return float Efficacy score (0.0 to 1.0)
     */
    public function getUnitEfficacyScore(string $unitId): float
    {
        $executions = $this->mesh->getKeysByPattern("entropy:executions:{$unitId}:*");
        if (empty($executions)) {
            return 1.0; // Default high score for new units
        }
        
        $successful = 0;
        $total = count($executions);
        
        foreach ($executions as $key) {
            $data = $this->mesh->get($key);
            if ($data && ($data['status'] === 'success' || $data['status'] === 'completed')) {
                $successful++;
            }
        }
        
        return $total > 0 ? $successful / $total : 1.0;
    }

    /**
     * Get unit entropy level
     * 
     * @param string $unitId Unit identifier
     * @return float Unit entropy level
     */
    public function getUnitEntropy(string $unitId): float
    {
        return 1.0 - $this->getUnitEfficacyScore($unitId);
    }

    /**
     * Get entropy threshold
     * 
     * @return float Entropy threshold
     */
    public function getEntropyThreshold(): float
    {
        return $this->config['max_entropy_threshold'];
    }

    /**
     * Get trend analysis for entropy metrics
     * 
     * @param string $metricName Metric name
     * @param int $timeWindow Time window in seconds
     * @return array Trend analysis
     */
    public function getTrendAnalysis(string $metricName, int $timeWindow = 3600): array
    {
        $cutoffTime = microtime(true) - $timeWindow;
        $keys = $this->mesh->getKeysByPattern("entropy:metrics:{$metricName}:*");
        
        $values = [];
        foreach ($keys as $key) {
            $data = $this->mesh->get($key);
            if ($data && $data['timestamp'] > $cutoffTime) {
                $values[] = [
                    'timestamp' => $data['timestamp'],
                    'value' => $data['value']
                ];
            }
        }
        
        // Sort by timestamp
        usort($values, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        $count = count($values);
        if ($count < 2) {
            return ['trend' => 'stable', 'direction' => 0, 'confidence' => 0.0];
        }
        
        // Simple linear trend calculation
        $firstValue = $values[0]['value'];
        $lastValue = $values[$count - 1]['value'];
        $direction = $lastValue - $firstValue;
        
        return [
            'trend' => $direction > 0 ? 'increasing' : ($direction < 0 ? 'decreasing' : 'stable'),
            'direction' => $direction,
            'confidence' => min(1.0, $count / 10), // Higher confidence with more data points
            'data_points' => $count
        ];
    }

    /**
     * Record an anomaly
     * 
     * @param string $type Anomaly type
     * @param array $details Anomaly details
     * @return void
     */
    public function recordAnomaly(string $type, array $details): void
    {
        $timestamp = microtime(true);
        $this->mesh->set("entropy:anomalies:{$type}:{$timestamp}", [
            'type' => $type,
            'details' => $details,
            'timestamp' => $timestamp,
            'entropy_level' => $this->getCurrentEntropyLevel()
        ]);
        
        // Publish anomaly alert
        $this->mesh->publish('system:alerts:anomaly', [
            'type' => $type,
            'details' => $details,
            'timestamp' => $timestamp
        ]);
    }

    /**
     * Get performance metrics
     * 
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $currentTime = microtime(true);
        $windowStart = $currentTime - 3600; // Last hour
        
        $executions = $this->mesh->getKeysByPattern('entropy:executions:*');
        $recentExecutions = 0;
        $avgExecutionTime = 0;
        $totalExecutionTime = 0;
        
        foreach ($executions as $key) {
            $data = $this->mesh->get($key);
            if ($data && $data['timestamp'] > $windowStart) {
                $recentExecutions++;
                if (isset($data['metrics']['execution_time'])) {
                    $totalExecutionTime += $data['metrics']['execution_time'];
                }
            }
        }
        
        if ($recentExecutions > 0) {
            $avgExecutionTime = $totalExecutionTime / $recentExecutions;
        }
        
        return [
            'recent_executions' => $recentExecutions,
            'avg_execution_time' => $avgExecutionTime,
            'entropy_level' => $this->getCurrentEntropyLevel(),
            'health_score' => 1.0 - $this->getCurrentEntropyLevel(),
            'window_start' => $windowStart,
            'window_end' => $currentTime
        ];
    }
}
