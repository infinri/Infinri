<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Tracing;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;

/**
 * Behavioral Analyzer - Pattern Recognition in Digital Consciousness
 * 
 * Analyzes behavioral patterns in SwarmUnit executions to identify
 * emergent behaviors, execution patterns, and system dynamics.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [])]
final class BehavioralAnalyzer
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = ConfigManager::getConfig('BehavioralAnalyzer', $config);
    }

    /**
     * Identify behavioral patterns in unit execution
     */
    public function identifyBehavioralPatterns(SwarmUnitInterface $unit, array $meshBefore, array $meshAfter): array
    {
        $identity = $unit->getIdentity();
        
        return [
            'execution_pattern' => $this->analyzeExecutionPattern($identity),
            'mesh_interaction_pattern' => $this->analyzeMeshInteractionPattern($meshBefore, $meshAfter),
            'temporal_pattern' => $this->analyzeTemporalPattern($identity),
            'dependency_pattern' => $this->analyzeDependencyPattern($identity)
        ];
    }

    /**
     * Analyze behavioral patterns across traces
     */
    public function analyzeBehavioralPatterns(array $traceHistory, ?string $unitId = null): array
    {
        $filteredTraces = $unitId 
            ? array_filter($traceHistory, fn($trace) => $trace['unit_id'] === $unitId)
            : $traceHistory;

        return [
            'execution_frequency' => $this->analyzeExecutionFrequency($filteredTraces),
            'mesh_access_patterns' => $this->analyzeMeshAccessPatterns($filteredTraces),
            'temporal_clustering' => $this->analyzeTemporalClustering($filteredTraces),
            'emergent_behaviors' => $this->identifyEmergentBehaviors($filteredTraces),
            'performance_trends' => $this->analyzePerformanceTrends($filteredTraces)
        ];
    }

    /**
     * Analyze execution frequency patterns
     */
    private function analyzeExecutionFrequency(array $traces): array
    {
        $unitFrequencies = [];
        $timeWindows = [];

        foreach ($traces as $trace) {
            $unitId = $trace['unit_id'];
            $timestamp = $trace['timestamp'];
            $hour = floor($timestamp / 3600) * 3600;

            if (!isset($unitFrequencies[$unitId])) {
                $unitFrequencies[$unitId] = 0;
            }
            $unitFrequencies[$unitId]++;

            if (!isset($timeWindows[$hour])) {
                $timeWindows[$hour] = 0;
            }
            $timeWindows[$hour]++;
        }

        return [
            'unit_frequencies' => $unitFrequencies,
            'time_windows' => $timeWindows,
            'peak_hour' => array_keys($timeWindows, max($timeWindows))[0] ?? null,
            'most_active_unit' => array_keys($unitFrequencies, max($unitFrequencies))[0] ?? null
        ];
    }

    /**
     * Analyze mesh access patterns
     */
    private function analyzeMeshAccessPatterns(array $traces): array
    {
        $keyAccess = [];
        $keyModifications = [];

        foreach ($traces as $trace) {
            if (isset($trace['mesh_changes'])) {
                foreach ($trace['mesh_changes'] as $change) {
                    $key = $change['key'];
                    
                    if (!isset($keyAccess[$key])) {
                        $keyAccess[$key] = 0;
                    }
                    $keyAccess[$key]++;

                    if ($change['type'] !== 'read') {
                        if (!isset($keyModifications[$key])) {
                            $keyModifications[$key] = 0;
                        }
                        $keyModifications[$key]++;
                    }
                }
            }
        }

        return [
            'key_access_frequency' => $keyAccess,
            'key_modification_frequency' => $keyModifications,
            'hottest_keys' => array_slice(array_keys(arsort($keyAccess) ? $keyAccess : []), 0, 10),
            'most_modified_keys' => array_slice(array_keys(arsort($keyModifications) ? $keyModifications : []), 0, 10)
        ];
    }

    /**
     * Analyze temporal clustering patterns
     */
    private function analyzeTemporalClustering(array $traces): array
    {
        $timestamps = array_column($traces, 'timestamp');
        sort($timestamps);

        $clusters = [];
        $currentCluster = [];
        $clusterThreshold = 300; // 5 minutes

        foreach ($timestamps as $timestamp) {
            if (empty($currentCluster) || ($timestamp - end($currentCluster)) <= $clusterThreshold) {
                $currentCluster[] = $timestamp;
            } else {
                if (count($currentCluster) >= $this->config['min_pattern_occurrences']) {
                    $clusters[] = $currentCluster;
                }
                $currentCluster = [$timestamp];
            }
        }

        if (count($currentCluster) >= $this->config['min_pattern_occurrences']) {
            $clusters[] = $currentCluster;
        }

        return [
            'cluster_count' => count($clusters),
            'largest_cluster_size' => max(array_map('count', $clusters)),
            'average_cluster_size' => array_sum(array_map('count', $clusters)) / max(1, count($clusters)),
            'clusters' => $clusters
        ];
    }

    /**
     * Identify emergent behaviors
     */
    private function identifyEmergentBehaviors(array $traces): array
    {
        $behaviors = [];

        // Look for cascade patterns
        $cascades = $this->detectCascadePatterns($traces);
        if (!empty($cascades)) {
            $behaviors['cascade_patterns'] = $cascades;
        }

        // Look for synchronization patterns
        $synchronizations = $this->detectSynchronizationPatterns($traces);
        if (!empty($synchronizations)) {
            $behaviors['synchronization_patterns'] = $synchronizations;
        }

        // Look for feedback loops
        $feedbackLoops = $this->detectFeedbackLoops($traces);
        if (!empty($feedbackLoops)) {
            $behaviors['feedback_loops'] = $feedbackLoops;
        }

        return $behaviors;
    }

    /**
     * Analyze performance trends
     */
    private function analyzePerformanceTrends(array $traces): array
    {
        $executionTimes = [];
        $timestamps = [];

        foreach ($traces as $trace) {
            if (isset($trace['execution_context']['duration'])) {
                $executionTimes[] = $trace['execution_context']['duration'];
                $timestamps[] = $trace['timestamp'];
            }
        }

        if (empty($executionTimes)) {
            return ['trend' => 'no_data'];
        }

        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $recentTimes = array_slice($executionTimes, -10);
        $recentAvg = array_sum($recentTimes) / count($recentTimes);

        return [
            'average_execution_time' => $avgTime,
            'recent_average' => $recentAvg,
            'trend' => $recentAvg > $avgTime * 1.1 ? 'degrading' : ($recentAvg < $avgTime * 0.9 ? 'improving' : 'stable'),
            'min_time' => min($executionTimes),
            'max_time' => max($executionTimes)
        ];
    }

    /**
     * Analyze execution pattern for a unit
     */
    private function analyzeExecutionPattern(UnitIdentity $identity): array
    {
        return [
            'unit_id' => $identity->id,
            'capabilities' => $identity->capabilities,
            'dependencies' => $identity->dependencies,
            'mesh_keys' => $identity->meshKeys
        ];
    }

    /**
     * Analyze mesh interaction pattern
     */
    private function analyzeMeshInteractionPattern(array $meshBefore, array $meshAfter): array
    {
        $reads = [];
        $writes = [];
        $deletes = [];

        foreach ($meshAfter as $key => $value) {
            if (!array_key_exists($key, $meshBefore)) {
                $writes[] = $key;
            } elseif ($meshBefore[$key] !== $value) {
                $writes[] = $key;
            } else {
                $reads[] = $key;
            }
        }

        foreach ($meshBefore as $key => $value) {
            if (!array_key_exists($key, $meshAfter)) {
                $deletes[] = $key;
            }
        }

        return [
            'reads' => $reads,
            'writes' => $writes,
            'deletes' => $deletes,
            'read_count' => count($reads),
            'write_count' => count($writes),
            'delete_count' => count($deletes)
        ];
    }

    /**
     * Analyze temporal pattern for a unit
     */
    private function analyzeTemporalPattern(UnitIdentity $identity): array
    {
        return [
            'execution_timestamp' => microtime(true),
            'unit_version' => $identity->version,
            'unit_hash' => $identity->hash
        ];
    }

    /**
     * Analyze dependency pattern for a unit
     */
    private function analyzeDependencyPattern(UnitIdentity $identity): array
    {
        return [
            'dependency_count' => count($identity->dependencies),
            'dependencies' => $identity->dependencies,
            'capability_count' => count($identity->capabilities),
            'capabilities' => $identity->capabilities
        ];
    }

    /**
     * Detect cascade patterns in traces
     */
    private function detectCascadePatterns(array $traces): array
    {
        // Simplified cascade detection
        return [];
    }

    /**
     * Detect synchronization patterns in traces
     */
    private function detectSynchronizationPatterns(array $traces): array
    {
        // Simplified synchronization detection
        return [];
    }

    /**
     * Detect feedback loops in traces
     */
    private function detectFeedbackLoops(array $traces): array
    {
        // Simplified feedback loop detection
        return [];
    }
}
