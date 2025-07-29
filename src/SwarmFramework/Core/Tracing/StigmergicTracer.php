<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Tracing;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Core\Tracing\BehavioralAnalyzer;
use Infinri\SwarmFramework\Core\Tracing\CausalityAnalyzer;
use Infinri\SwarmFramework\Core\Tracing\PheromoneAnalyzer;
use Infinri\SwarmFramework\Core\Tracing\TraceSpan;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Psr\Log\LoggerInterface;

/**
 * Stigmergic Tracer - The Memory Trails of Digital Consciousness
 * 
 * Coordinates tracing activities by delegating specialized analysis
 * to focused components. Maintains trace history and provides
 * unified access to behavioral insights.
 * 
 * @architecture Behavioral trace coordination with specialized analyzers
 * @reference swarm_pattern_originals_definitions.md → Stigmergic Tracing Pattern
 * @reference infinri_blueprint.md → FR-CORE-005
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['PheromoneAnalyzer', 'BehavioralAnalyzer', 'CausalityAnalyzer'])]
final class StigmergicTracer
{
    use LoggerTrait;

    private SemanticMeshInterface $mesh;
    private PheromoneAnalyzer $pheromoneAnalyzer;
    private BehavioralAnalyzer $behavioralAnalyzer;
    private CausalityAnalyzer $causalityAnalyzer;
    private array $activeSpans = [];
    private array $traceHistory = [];
    private array $config;
    private int $traceCounter = 0;

    /**
     * @param LoggerInterface $logger Logger instance
     * @param PheromoneAnalyzer $pheromoneAnalyzer Pheromone analysis component
     * @param BehavioralAnalyzer $behavioralAnalyzer Behavioral analysis component
     * @param CausalityAnalyzer $causalityAnalyzer Causality analysis component
     * @param array $config Tracer configuration
     */
    public function __construct(
        LoggerInterface $logger,
        PheromoneAnalyzer $pheromoneAnalyzer,
        BehavioralAnalyzer $behavioralAnalyzer,
        CausalityAnalyzer $causalityAnalyzer,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->pheromoneAnalyzer = $pheromoneAnalyzer;
        $this->behavioralAnalyzer = $behavioralAnalyzer;
        $this->causalityAnalyzer = $causalityAnalyzer;
        $this->config = ConfigManager::getConfig('StigmergicTracer', $config);
    }

    /**
     * Trace the execution of a SwarmUnit
     * 
     * @param SwarmUnitInterface $unit The executed unit
     * @param array $meshBefore Mesh state before execution
     * @param array $meshAfter Mesh state after execution
     * @return void
     */
    public function traceExecution(SwarmUnitInterface $unit, array $meshBefore, array $meshAfter): void
    {
        // Apply sampling rate
        $randomValue = mt_rand(0, 1000) / 1000.0;
        if ($randomValue > $this->config['trace_sampling_rate']) {
            return;
        }

        $identity = $unit->getIdentity();
        $traceId = $this->generateTraceId();
        $timestamp = PerformanceTimer::now();

        // Delegate analysis to specialized components
        $pheromoneTrails = $this->pheromoneAnalyzer->extractPheromoneTrails($meshBefore, $meshAfter);
        $behavioralPatterns = $this->behavioralAnalyzer->identifyBehavioralPatterns($unit, $meshBefore, $meshAfter);
        $meshChanges = $this->analyzeMeshChanges($meshBefore, $meshAfter);

        $trace = [
            'trace_id' => $traceId,
            'unit_id' => $identity->id,
            'unit_version' => $identity->version,
            'unit_hash' => $identity->hash,
            'timestamp' => $timestamp,
            'execution_context' => [
                'priority' => $unit->getPriority(),
                'mutex_group' => $unit->getMutexGroup(),
                'cooldown' => $unit->getCooldown(),
                'timeout' => $unit->getTimeout()
            ],
            'mesh_changes' => $meshChanges,
            'pheromone_trails' => $pheromoneTrails,
            'behavioral_patterns' => $behavioralPatterns
        ];

        // Store in history
        $this->traceHistory[$traceId] = $trace;
        $this->maintainHistorySize();

        // Log the trace
        $this->logger->info('Unit execution traced', [
            'trace_id' => $traceId,
            'unit_id' => $identity->id,
            'mesh_changes' => count($trace['mesh_changes']),
            'pheromone_intensity' => $this->pheromoneAnalyzer->calculatePheromoneIntensity($pheromoneTrails)
        ]);

        // Emit trace event for real-time monitoring
        $this->emitTraceEvent($trace);
    }

    /**
     * Create an execution span for detailed tracing
     */
    public function createExecutionSpan(UnitIdentity $identity): TraceSpan
    {
        $spanId = $this->generateSpanId();
        $span = new TraceSpan($spanId, $identity, PerformanceTimer::now());
        
        $this->activeSpans[$spanId] = $span;
        
        return $span;
    }

    /**
     * Correlate execution span with mesh changes
     */
    public function correlateWithMeshChanges(TraceSpan $span, array $mutations): void
    {
        $span->addMeshMutations($mutations);
        
        // Build causality chain using specialized analyzer
        $causalityChain = $this->causalityAnalyzer->buildCausalityChain($span, $mutations, $this->traceHistory);
        $span->setCausalityChain($causalityChain);
    }

    /**
     * Get trace history for analysis
     */
    public function getTraceHistory(array $filters = []): array
    {
        if (empty($filters)) {
            return $this->traceHistory;
        }

        $filtered = $this->traceHistory;

        if (isset($filters['unit_id'])) {
            $filtered = array_filter($filtered, fn($trace) => $trace['unit_id'] === $filters['unit_id']);
        }

        if (isset($filters['time_range'])) {
            $start = $filters['time_range']['start'] ?? 0;
            $end = $filters['time_range']['end'] ?? PHP_FLOAT_MAX;
            $filtered = array_filter($filtered, fn($trace) => 
                $trace['timestamp'] >= $start && $trace['timestamp'] <= $end
            );
        }

        return $filtered;
    }

    /**
     * Analyze behavioral patterns across traces
     */
    public function analyzeBehavioralPatterns(?string $unitId = null): array
    {
        return $this->behavioralAnalyzer->analyzeBehavioralPatterns($this->traceHistory, $unitId);
    }

    /**
     * Get pheromone trail intensity for a mesh key
     */
    public function getPheromoneIntensity(string $meshKey): float
    {
        return $this->pheromoneAnalyzer->getPheromoneIntensity($meshKey, $this->traceHistory);
    }

    /**
     * Clear old traces based on retention policy
     */
    public function clearOldTraces(): int
    {
        $cutoffTime = time() - ($this->config['trace_retention_hours'] * 3600);
        $originalCount = count($this->traceHistory);
        
        $this->traceHistory = array_filter($this->traceHistory, function($trace) use ($cutoffTime) {
            return $trace['timestamp'] > $cutoffTime;
        });

        $clearedCount = $originalCount - count($this->traceHistory);
        
        if ($clearedCount > 0) {
            $this->logger->info('Cleared old traces', ['count' => $clearedCount]);
        }

        return $clearedCount;
    }

    /**
     * Generate unique trace ID
     */
    private function generateTraceId(): string
    {
        return 'trace_' . uniqid() . '_' . (++$this->traceCounter);
    }

    /**
     * Generate unique span ID
     */
    private function generateSpanId(): string
    {
        return 'span_' . uniqid() . '_' . PerformanceTimer::now();
    }

    /**
     * Analyze changes between mesh states
     */
    private function analyzeMeshChanges(array $before, array $after): array
    {
        $changes = [];

        // Find additions and modifications
        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before)) {
                $changes[] = [
                    'key' => $key,
                    'type' => 'create',
                    'before' => null,
                    'after' => $value
                ];
            } elseif ($before[$key] !== $value) {
                $changes[] = [
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
                $changes[] = [
                    'key' => $key,
                    'type' => 'delete',
                    'before' => $value,
                    'after' => null
                ];
            }
        }

        return $changes;
    }

    /**
     * Maintain trace history size within limits
     */
    private function maintainHistorySize(): void
    {
        if (count($this->traceHistory) > $this->config['max_history_size']) {
            // Remove oldest traces
            $sortedTraces = $this->traceHistory;
            uasort($sortedTraces, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
            
            $toRemove = count($this->traceHistory) - $this->config['max_history_size'];
            $keysToRemove = array_slice(array_keys($sortedTraces), 0, $toRemove);
            
            foreach ($keysToRemove as $key) {
                unset($this->traceHistory[$key]);
            }
        }
    }

    /**
     * Emit trace event for real-time monitoring
     */
    private function emitTraceEvent(array $trace): void
    {
        // Placeholder for event emission
        // In a real implementation, this would emit to a message queue or event bus
        $this->logger->debug('Trace event emitted', ['trace_id' => $trace['trace_id']]);
    }
}
