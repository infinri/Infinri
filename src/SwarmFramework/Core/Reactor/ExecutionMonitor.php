<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Safety\SafetyLimitsEnforcer;
use Infinri\SwarmFramework\Core\Tracing\StigmergicTracer;
use Psr\Log\LoggerInterface;

/**
 * Execution Monitor - Performance Monitoring and Health Metrics
 * 
 * Monitors SwarmUnit execution with comprehensive performance tracking,
 * health metrics, and safety enforcement during unit execution.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface', 'SafetyLimitsEnforcer', 'StigmergicTracer'])]
final class ExecutionMonitor
{
    use LoggerTrait;

    private SafetyLimitsEnforcer $safetyEnforcer;
    private StigmergicTracer $tracer;
    private array $healthMetrics = [];
    private array $config;

    public function __construct(
        LoggerInterface $logger,
        SafetyLimitsEnforcer $safetyEnforcer,
        StigmergicTracer $tracer,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->safetyEnforcer = $safetyEnforcer;
        $this->tracer = $tracer;
        $this->config = ConfigManager::getConfig('ExecutionMonitor', $config);
    }

    /**
     * Execute units with comprehensive monitoring
     */
    public function executeUnitsWithMonitoring(array $prioritizedUnits, SemanticMeshInterface $mesh): array
    {
        $executionResults = [];
        $executionStart = PerformanceTimer::now();

        foreach ($prioritizedUnits as $unitData) {
            $unit = $unitData['unit'];
            $unitId = $unit->getIdentity()->id;

            try {
                // Pre-execution safety checks
                $this->safetyEnforcer->checkExecutionStart($unitId);
                
                // Execute with monitoring
                $result = $this->executeUnitWithMonitoring($unit, $mesh);
                $executionResults[] = $result;

                // Update health metrics
                $this->updateUnitHealthMetrics($unitId, $result);

            } catch (\Exception $e) {
                $this->logger->error('Unit execution failed', $this->buildErrorContext('unit_execution', $e, [
                    'unit_id' => $unitId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]));

                $executionResults[] = [
                    'unit_id' => $unitId,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'duration' => 0,
                    'memory_used' => 0
                ];
            }
        }

        $totalDuration = PerformanceTimer::duration($executionStart);
        
        $this->logger->info('Unit execution batch completed', $this->buildPerformanceContext('batch_execution', $executionStart, [
            'units_executed' => count($prioritizedUnits),
            'successful_executions' => count(array_filter($executionResults, fn($r) => $r['success'])),
            'total_duration' => $totalDuration
        ]));

        return $executionResults;
    }

    /**
     * Execute a single unit with detailed monitoring
     */
    private function executeUnitWithMonitoring(SwarmUnitInterface $unit, SemanticMeshInterface $mesh): array
    {
        $unitId = $unit->getIdentity()->id;
        $executionStart = PerformanceTimer::now();
        $memoryStart = memory_get_usage(true);

        // Capture mesh state before execution
        $meshBefore = $mesh->snapshot();

        try {
            // Set execution timeout
            set_time_limit($this->config['execution_timeout_seconds']);

            // Execute the unit
            $unit->act($mesh);

            // Capture mesh state after execution
            $meshAfter = $mesh->snapshot();

            $executionEnd = PerformanceTimer::now();
            $memoryEnd = memory_get_usage(true);
            $duration = $executionEnd - $executionStart;
            $memoryUsed = $memoryEnd - $memoryStart;

            // Record execution completion for safety tracking
            $this->safetyEnforcer->recordExecutionEnd($unitId);

            // Trace execution if enabled
            if ($this->config['enable_detailed_tracing']) {
                $this->tracer->traceExecution($unit, $meshBefore, $meshAfter);
            }

            $result = [
                'unit_id' => $unitId,
                'success' => true,
                'duration' => $duration,
                'memory_used' => $memoryUsed,
                'mesh_changes' => $this->calculateMeshMutations($meshBefore, $meshAfter),
                'execution_timestamp' => $executionStart
            ];

            $this->logger->debug('Unit executed successfully', [
                'unit_id' => $unitId,
                'duration_ms' => $duration * 1000,
                'memory_mb' => $memoryUsed / (1024 * 1024),
                'mesh_changes' => count($result['mesh_changes'])
            ]);

            return $result;

        } catch (\Exception $e) {
            $executionEnd = PerformanceTimer::now();
            $duration = $executionEnd - $executionStart;

            throw ExceptionFactory::runtime(
                "Unit execution failed: {$e->getMessage()}",
                ['unit_id' => $unitId, 'duration' => $duration, 'original_error' => $e->getMessage()],
                $e
            );
        } finally {
            // Reset time limit
            set_time_limit(0);
        }
    }

    /**
     * Update health metrics for a unit
     */
    private function updateUnitHealthMetrics(string $unitId, array $executionResult): void
    {
        if (!isset($this->healthMetrics[$unitId])) {
            $this->healthMetrics[$unitId] = [
                'executions' => 0,
                'successes' => 0,
                'failures' => 0,
                'total_duration' => 0.0,
                'avg_duration' => 0.0,
                'total_memory' => 0,
                'avg_memory' => 0,
                'last_execution' => null,
                'error_rate' => 0.0
            ];
        }

        $metrics = &$this->healthMetrics[$unitId];
        $metrics['executions']++;
        $metrics['last_execution'] = PerformanceTimer::now();

        if ($executionResult['success']) {
            $metrics['successes']++;
            $metrics['total_duration'] += $executionResult['duration'];
            $metrics['total_memory'] += $executionResult['memory_used'];
            
            // Update averages
            $metrics['avg_duration'] = $metrics['total_duration'] / $metrics['successes'];
            $metrics['avg_memory'] = $metrics['total_memory'] / $metrics['successes'];
        } else {
            $metrics['failures']++;
        }

        // Update error rate
        $metrics['error_rate'] = $metrics['failures'] / $metrics['executions'];
    }

    /**
     * Get comprehensive health metrics
     */
    public function getHealthMetrics(): array
    {
        $totalExecutions = 0;
        $totalSuccesses = 0;
        $totalFailures = 0;
        $avgDuration = 0.0;
        $avgMemory = 0;

        foreach ($this->healthMetrics as $unitMetrics) {
            $totalExecutions += $unitMetrics['executions'];
            $totalSuccesses += $unitMetrics['successes'];
            $totalFailures += $unitMetrics['failures'];
        }

        if ($totalSuccesses > 0) {
            $totalDuration = array_sum(array_column($this->healthMetrics, 'total_duration'));
            $totalMemoryUsed = array_sum(array_column($this->healthMetrics, 'total_memory'));
            $avgDuration = $totalDuration / $totalSuccesses;
            $avgMemory = $totalMemoryUsed / $totalSuccesses;
        }

        return [
            'total_executions' => $totalExecutions,
            'total_successes' => $totalSuccesses,
            'total_failures' => $totalFailures,
            'overall_success_rate' => $totalExecutions > 0 ? $totalSuccesses / $totalExecutions : 1.0,
            'avg_execution_duration' => $avgDuration,
            'avg_memory_usage' => $avgMemory,
            'units_monitored' => count($this->healthMetrics),
            'unit_metrics' => $this->healthMetrics
        ];
    }

    /**
     * Update system health metrics from execution results
     */
    public function updateSystemHealthMetrics(array $executionResults): void
    {
        foreach ($executionResults as $result) {
            if (isset($result['unit_id'])) {
                $this->updateUnitHealthMetrics($result['unit_id'], $result);
            }
        }
    }

    /**
     * Calculate mesh mutations between before and after states
     */
    private function calculateMeshMutations(array $before, array $after): array
    {
        $mutations = [];

        // Find additions and modifications
        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before)) {
                $mutations[] = [
                    'key' => $key,
                    'type' => 'create',
                    'before' => null,
                    'after' => $value
                ];
            } elseif ($before[$key] !== $value) {
                $mutations[] = [
                    'key' => $key,
                    'type' => 'update',
                    'before' => $before[$key],
                    'after' => $value
                ];
            }
        }

        // Find deletions
        foreach ($before as $key => $value) {
            if (!array_key_exists($key, $after)) {
                $mutations[] = [
                    'key' => $key,
                    'type' => 'delete',
                    'before' => $value,
                    'after' => null
                ];
            }
        }

        return $mutations;
    }

    /**
     * Check if system is healthy
     */
    public function isSystemHealthy(): bool
    {
        $metrics = $this->getHealthMetrics();
        
        // System is healthy if:
        // 1. Overall success rate > 95%
        // 2. Average execution time < 100ms
        // 3. Average memory usage < 200MB
        return $metrics['overall_success_rate'] >= 0.95 &&
               $metrics['avg_execution_duration'] < 0.1 &&
               $metrics['avg_memory_usage'] < (200 * 1024 * 1024);
    }

    /**
     * Get performance trends
     */
    public function getPerformanceTrends(): array
    {
        $trends = [];
        
        foreach ($this->healthMetrics as $unitId => $metrics) {
            $trends[$unitId] = [
                'error_rate_trend' => $this->calculateErrorRateTrend($metrics),
                'performance_trend' => $this->calculatePerformanceTrend($metrics),
                'memory_trend' => $this->calculateMemoryTrend($metrics)
            ];
        }

        return $trends;
    }

    /**
     * Calculate error rate trend for a unit
     */
    private function calculateErrorRateTrend(array $metrics): string
    {
        if ($metrics['error_rate'] < 0.01) {
            return 'excellent';
        } elseif ($metrics['error_rate'] < 0.05) {
            return 'good';
        } elseif ($metrics['error_rate'] < 0.1) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate performance trend for a unit
     */
    private function calculatePerformanceTrend(array $metrics): string
    {
        if ($metrics['avg_duration'] < 0.01) {
            return 'excellent';
        } elseif ($metrics['avg_duration'] < 0.05) {
            return 'good';
        } elseif ($metrics['avg_duration'] < 0.1) {
            return 'warning';
        } else {
            return 'slow';
        }
    }

    /**
     * Calculate memory trend for a unit
     */
    private function calculateMemoryTrend(array $metrics): string
    {
        $memoryMB = $metrics['avg_memory'] / (1024 * 1024);
        
        if ($memoryMB < 50) {
            return 'excellent';
        } elseif ($memoryMB < 100) {
            return 'good';
        } elseif ($memoryMB < 200) {
            return 'warning';
        } else {
            return 'high';
        }
    }
}
