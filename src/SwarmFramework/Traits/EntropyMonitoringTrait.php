<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

use Infinri\SwarmFramework\Core\Monitoring\EntropyMonitor;

/**
 * EntropyMonitoringTrait - Entropy Consciousness Monitoring
 * 
 * Provides consciousness-level entropy monitoring capabilities to SwarmUnits.
 * Implements self-awareness through entropy tracking and health metrics.
 * 
 * @architecture Reusable entropy consciousness behaviors
 * @reference infinri_blueprint.md → FR-CORE-028 (Health Monitoring)
 * @tactic TAC-ENTROPY-001 (Entropy monitoring with auto-pruning)
 * @author Infinri Framework
 * @version 1.0.0
 */
trait EntropyMonitoringTrait
{
    protected EntropyMonitor $entropy;

    /**
     * Record health metric for consciousness monitoring
     * 
     * @param string $metric Metric name
     * @param mixed $value Metric value
     * @return void
     */
    protected function recordHealthMetric(string $metric, mixed $value): void
    {
        $this->entropy->recordMetric($this->getIdentity()->id, $metric, $value);
    }

    /**
     * Get unit efficacy score based on entropy analysis
     * 
     * @return float Efficacy score (0.0 to 1.0)
     */
    protected function getUnitEfficacyScore(): float
    {
        return $this->entropy->getUnitEfficacyScore($this->getIdentity()->id);
    }

    /**
     * Check if unit entropy is within acceptable limits
     * 
     * @return bool True if entropy is acceptable
     */
    protected function isEntropyAcceptable(): bool
    {
        $currentEntropy = $this->getCurrentEntropy();
        $threshold = $this->getEntropyThreshold();
        
        return $currentEntropy <= $threshold;
    }

    /**
     * Get current entropy level for this unit
     * 
     * @return float Current entropy level
     */
    protected function getCurrentEntropy(): float
    {
        return $this->entropy->getUnitEntropy($this->getIdentity()->id);
    }

    /**
     * Get entropy threshold for this unit
     * 
     * @return float Entropy threshold
     */
    protected function getEntropyThreshold(): float
    {
        return $this->entropy->getEntropyThreshold($this->getIdentity()->id);
    }

    /**
     * Record execution entropy for consciousness tracking
     * 
     * @param string $operation Operation being performed
     * @param float $executionTime Execution time
     * @param int $meshMutations Number of mesh mutations
     * @return void
     */
    protected function recordExecutionEntropy(string $operation, float $executionTime, int $meshMutations): void
    {
        $entropyData = [
            'operation' => $operation,
            'execution_time' => $executionTime,
            'mesh_mutations' => $meshMutations,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => microtime(true)
        ];

        $this->entropy->recordExecution($this->getIdentity()->id, 'entropy_tracking', $entropyData);
    }

    /**
     * Get entropy health status
     * 
     * @return array Entropy health status
     */
    protected function getEntropyHealthStatus(): array
    {
        $currentEntropy = $this->getCurrentEntropy();
        $threshold = $this->getEntropyThreshold();
        $ratio = $threshold > 0 ? $currentEntropy / $threshold : 0;

        return [
            'current_entropy' => $currentEntropy,
            'threshold' => $threshold,
            'ratio' => $ratio,
            'status' => $this->determineEntropyStatus($ratio),
            'requires_attention' => $ratio > 0.8,
            'critical' => $ratio > 1.0
        ];
    }

    /**
     * Determine entropy status based on ratio
     * 
     * @param float $ratio Entropy ratio (current/threshold)
     * @return string Status description
     */
    private function determineEntropyStatus(float $ratio): string
    {
        return match(true) {
            $ratio <= 0.5 => 'excellent',
            $ratio <= 0.7 => 'good',
            $ratio <= 0.8 => 'acceptable',
            $ratio <= 1.0 => 'concerning',
            default => 'critical'
        };
    }

    /**
     * Check if auto-pruning should be triggered
     * 
     * @return bool True if auto-pruning should be triggered
     */
    protected function shouldTriggerAutoPruning(): bool
    {
        $healthStatus = $this->getEntropyHealthStatus();
        return $healthStatus['ratio'] > 0.9;
    }

    /**
     * Get entropy trend analysis
     * 
     * @param int $periodMinutes Period to analyze in minutes
     * @return array Entropy trend analysis
     */
    protected function getEntropyTrend(int $periodMinutes = 60): array
    {
        return $this->entropy->getTrendAnalysis($this->getIdentity()->id, $periodMinutes);
    }

    /**
     * Record entropy anomaly for consciousness awareness
     * 
     * @param string $anomalyType Type of anomaly
     * @param array $context Anomaly context
     * @return void
     */
    protected function recordEntropyAnomaly(string $anomalyType, array $context): void
    {
        $details = array_merge($context, ['unit_id' => $this->getIdentity()->id]);
        $this->entropy->recordAnomaly($anomalyType, $details);
    }

    /**
     * Get performance degradation indicators
     * 
     * @return array Performance degradation indicators
     */
    protected function getPerformanceDegradation(): array
    {
        $metrics = $this->entropy->getPerformanceMetrics($this->getIdentity()->id);
        
        return [
            'execution_time_increase' => $metrics['avg_execution_time_trend'] ?? 0,
            'error_rate_increase' => $metrics['error_rate_trend'] ?? 0,
            'memory_usage_increase' => $metrics['memory_usage_trend'] ?? 0,
            'overall_degradation' => $this->calculateOverallDegradation($metrics)
        ];
    }

    /**
     * Calculate overall performance degradation score
     * 
     * @param array $metrics Performance metrics
     * @return float Degradation score (0.0 = no degradation, 1.0 = severe degradation)
     */
    private function calculateOverallDegradation(array $metrics): float
    {
        $executionTimeTrend = $metrics['avg_execution_time_trend'] ?? 0;
        $errorRateTrend = $metrics['error_rate_trend'] ?? 0;
        $memoryUsageTrend = $metrics['memory_usage_trend'] ?? 0;

        // Weighted average of degradation factors
        $degradation = (
            ($executionTimeTrend * 0.4) +
            ($errorRateTrend * 0.4) +
            ($memoryUsageTrend * 0.2)
        );

        return max(0.0, min(1.0, $degradation));
    }

    /**
     * Get entropy-based recommendations
     * 
     * @return array Recommendations for improving entropy
     */
    protected function getEntropyRecommendations(): array
    {
        $healthStatus = $this->getEntropyHealthStatus();
        $degradation = $this->getPerformanceDegradation();
        
        $recommendations = [];

        if ($healthStatus['ratio'] > 0.8) {
            $recommendations[] = 'reduce_execution_frequency';
            $recommendations[] = 'optimize_mesh_operations';
        }

        if ($degradation['execution_time_increase'] > 0.2) {
            $recommendations[] = 'optimize_algorithms';
            $recommendations[] = 'reduce_computational_complexity';
        }

        if ($degradation['memory_usage_increase'] > 0.3) {
            $recommendations[] = 'implement_memory_cleanup';
            $recommendations[] = 'reduce_data_retention';
        }

        if ($degradation['error_rate_increase'] > 0.1) {
            $recommendations[] = 'improve_error_handling';
            $recommendations[] = 'add_input_validation';
        }

        return $recommendations;
    }

    /**
     * Get comprehensive entropy report for consciousness analysis
     * 
     * @return array Comprehensive entropy report
     */
    protected function getEntropyReport(): array
    {
        return [
            'unit_id' => $this->getIdentity()->id,
            'timestamp' => microtime(true),
            'health_status' => $this->getEntropyHealthStatus(),
            'efficacy_score' => $this->getUnitEfficacyScore(),
            'trend_analysis' => $this->getEntropyTrend(),
            'performance_degradation' => $this->getPerformanceDegradation(),
            'recommendations' => $this->getEntropyRecommendations(),
            'auto_pruning_needed' => $this->shouldTriggerAutoPruning()
        ];
    }
}
