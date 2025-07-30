<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Base;

use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Infinri\SwarmFramework\Core\Attributes\UnitIdentity;
use Infinri\SwarmFramework\Core\Monitoring\EntropyMonitor;
use Infinri\SwarmFramework\Core\Validation\EthicalValidator;
use Infinri\SwarmFramework\Core\Reactor\ThrottlingManager;
use Infinri\SwarmFramework\Core\Temporal\TemporalEngine;
use Infinri\SwarmFramework\Core\AccessControl\MeshACLManager;
use Infinri\SwarmFramework\Core\Tracing\StigmergicTracer;
use Infinri\SwarmFramework\Exceptions\MeshACLViolationException;
use Infinri\SwarmFramework\Exceptions\EthicalValidationException;
use Infinri\SwarmFramework\Exceptions\FallbackDepthExceededException;
use Infinri\SwarmFramework\Traits\MeshACLTrait;
use Infinri\SwarmFramework\Traits\EthicalValidationTrait;
use Infinri\SwarmFramework\Traits\TemporalLogicTrait;
use Infinri\SwarmFramework\Traits\EntropyMonitoringTrait;
use Infinri\SwarmFramework\Traits\ConsciousnessMonitoringTrait;
use Infinri\SwarmFramework\Traits\FallbackManagementTrait;

/**
 * AbstractSwarmUnit - The Conscious Cell Template
 * 
 * Base class implementing digital consciousness patterns for all SwarmUnits.
 * Each unit exhibits self-monitoring, ethical reasoning, and adaptive responses.
 * 
 * @architecture Base class implementing digital consciousness patterns
 * @reference infinri_blueprint.md → FR-CORE-028 (Health Monitoring)
 * @reference infinri_blueprint.md → FR-CORE-041 (Ethical Boundaries)
 * @reference infinri_blueprint.md → FR-CORE-006 (Mesh Access Control)
 * @tactic TAC-ENTROPY-001 (Entropy monitoring with auto-pruning)
 * @author Infinri Framework
 * @version 1.0.0
 */
abstract class AbstractSwarmUnit implements SwarmUnitInterface
{
    use MeshACLTrait;
    use EthicalValidationTrait;
    use TemporalLogicTrait;
    use EntropyMonitoringTrait;
    use ConsciousnessMonitoringTrait;
    use FallbackManagementTrait;

    protected EntropyMonitor $entropy;
    protected EthicalValidator $ethics;
    protected ThrottlingManager $throttling;
    protected TemporalEngine $temporal;
    protected MeshACLManager $aclManager;
    protected StigmergicTracer $tracer;

    // Consciousness-level state tracking
    private bool $isInitialized = false;

    /**
     * Initialize consciousness-level services
     * 
     * @param EntropyMonitor $entropy Entropy monitoring service
     * @param EthicalValidator $ethics Ethical validation service
     * @param ThrottlingManager $throttling Throttling management service
     * @param TemporalEngine $temporal Temporal logic engine
     * @param MeshACLManager $aclManager ACL management service
     * @param StigmergicTracer $tracer Stigmergic tracing service
     */
    public function __construct(
        EntropyMonitor $entropy,
        EthicalValidator $ethics,
        ThrottlingManager $throttling,
        TemporalEngine $temporal,
        MeshACLManager $aclManager,
        StigmergicTracer $tracer
    ) {
        $this->entropy = $entropy;
        $this->ethics = $ethics;
        $this->throttling = $throttling;
        $this->temporal = $temporal;
        $this->aclManager = $aclManager;
        $this->tracer = $tracer;
        
        $this->initializeHealthMetrics();
    }

    /**
     * Initialize the unit with consciousness-level awareness
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     * @throws UnitInitializationException If initialization fails
     */
    public function initialize(SemanticMeshInterface $mesh): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->recordStigmergicTrace('unit_initialization', [
            'unit_id' => $this->getIdentity()->id,
            'timestamp' => microtime(true)
        ]);

        $this->performConsciousnessInitialization($mesh);
        $this->isInitialized = true;

        $this->recordExecution('initialized', [
            'initialization_time' => microtime(true),
            'mesh_keys_accessed' => 0
        ]);
    }

    /**
     * Execute with consciousness-level monitoring and validation
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh for state mutations
     * @return void
     * @throws UnitExecutionException If execution fails
     */
    final public function act(SemanticMeshInterface $mesh): void
    {
        $startTime = microtime(true);
        $meshKeysBefore = count($mesh->getStats()['keys'] ?? []);

        try {
            // Pre-execution consciousness checks
            $this->performPreExecutionChecks($mesh);
            
            // Execute the unit's specific behavior
            $this->executeUnitLogic($mesh);
            
            // Post-execution consciousness validation
            $this->performPostExecutionValidation($mesh, $startTime, $meshKeysBefore);
            
        } catch (\Throwable $e) {
            $this->handleExecutionFailure($e, $mesh, $startTime);
            throw $e;
        }
    }

    /**
     * Abstract method for unit-specific logic implementation
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     */
    abstract protected function executeUnitLogic(SemanticMeshInterface $mesh): void;

    /**
     * Abstract method for consciousness-level initialization
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     */
    abstract protected function performConsciousnessInitialization(SemanticMeshInterface $mesh): void;



    /**
     * Ethical validation with consciousness-level reasoning
     * Per FR-CORE-041 ethical boundaries
     * 
     * @param array $context Context to validate
     * @return bool True if ethical, false otherwise
     * @throws EthicalValidationException If validation fails critically
     */
    protected function validateEthicalContext(array $context): bool
    {
        $ethicalScore = $this->ethics->validateContent($context);
        
        if ($ethicalScore < 0.5) {
            $this->recordExecution('ethical_violation_critical', ['ethical_score' => $ethicalScore]);
            throw new EthicalValidationException($ethicalScore, $context);
        }
        
        if ($ethicalScore < 0.7) {
            $this->recordExecution('ethical_violation', ['ethical_score' => $ethicalScore]);
            return false;
        }
        
        return true;
    }

    /**
     * Mesh ACL compliance checking with namespace awareness
     * Implements FR-CORE-006 mesh access control
     * 
     * @param string $meshKey Mesh key to access
     * @param string $operation Operation type (read/write/delete)
     * @return bool True if access allowed
     * @throws MeshACLViolationException If access denied
     */
    protected function checkMeshACL(string $meshKey, string $operation): bool
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        $hasAccess = $this->aclManager->validateAccess($meshKey, $operation, $unitCapabilities);
        
        if (!$hasAccess) {
            throw new MeshACLViolationException($meshKey, $operation, $unitCapabilities);
        }
        
        return true;
    }

    /**
     * Stigmergic trace recording for conscious debugging
     * Each unit leaves "pheromone trails" for system awareness
     * 
     * @param string $action Action being performed
     * @param array $context Action context
     * @return void
     */
    protected function recordStigmergicTrace(string $action, array $context): void
    {
        $this->tracer->recordTrace([
            'unit_id' => $this->getIdentity()->id,
            'action' => $action,
            'context' => $context,
            'timestamp' => microtime(true),
            'mesh_snapshot_hash' => $this->calculateMeshHash($context)
        ]);
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
            'execution_count' => count($this->executionHistory),
            'last_execution' => $this->lastExecutionTime
        ];
    }

    /**
     * Calculate mesh state hash for integrity checking
     * 
     * @param array $context Context data
     * @return string Hash of mesh state
     */
    private function calculateMeshHash(array $context): string
    {
        return hash('sha256', serialize($context));
    }

    /**
     * Perform pre-execution consciousness checks
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     */
    private function performPreExecutionChecks(SemanticMeshInterface $mesh): void
    {
        // Check if unit is healthy
        if (!$this->isHealthy()) {
            $this->handleFallbackStrategy('unit_unhealthy');
            return;
        }

        // Validate ethical context
        $meshSnapshot = $mesh->snapshot(['*']);
        if (!$this->validateEthicalContext($meshSnapshot)) {
            $this->handleFallbackStrategy('ethical_validation_failed');
            return;
        }

        // Record pre-execution trace
        $this->recordStigmergicTrace('pre_execution', [
            'mesh_keys' => array_keys($meshSnapshot),
            'health_status' => $this->getHealthMetrics()
        ]);
    }

    /**
     * Perform post-execution validation and monitoring
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @param float $startTime Execution start time
     * @param int $meshKeysBefore Mesh keys count before execution
     * @return void
     */
    private function performPostExecutionValidation(
        SemanticMeshInterface $mesh, 
        float $startTime, 
        int $meshKeysBefore
    ): void {
        $executionTime = microtime(true) - $startTime;
        $meshKeysAfter = count($mesh->getStats()['keys'] ?? []);
        $mutations = $meshKeysAfter - $meshKeysBefore;

        $this->lastExecutionTime = microtime(true);
        
        $this->recordExecution('completed', [
            'time' => $executionTime,
            'mutations' => $mutations,
            'confidence' => $this->calculateConfidenceScore($executionTime, $mutations)
        ]);

        $this->updateHealthMetrics($executionTime, $mutations);
    }

    /**
     * Handle execution failure with consciousness-level recovery
     * 
     * @param \Throwable $exception The exception that occurred
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @param float $startTime Execution start time
     * @return void
     */
    private function handleExecutionFailure(
        \Throwable $exception, 
        SemanticMeshInterface $mesh, 
        float $startTime
    ): void {
        $executionTime = microtime(true) - $startTime;
        
        $this->recordExecution('failed', [
            'time' => $executionTime,
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception)
        ]);

        $this->recordStigmergicTrace('execution_failure', [
            'error' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'mesh_state' => $mesh->getStats()
        ]);

        $this->updateHealthMetrics($executionTime, 0, true);
    }

    /**
     * Calculate confidence score based on execution metrics
     * 
     * @param float $executionTime Execution time
     * @param int $mutations Number of mesh mutations
     * @return float Confidence score (0.0 to 1.0)
     */
    private function calculateConfidenceScore(float $executionTime, int $mutations): float
    {
        // Base confidence on execution time and mutation efficiency
        $timeScore = min(1.0, 1.0 - ($executionTime / $this->getTimeout()));
        $mutationScore = $mutations > 0 ? min(1.0, $mutations / 10.0) : 0.5;
        
        return ($timeScore + $mutationScore) / 2.0;
    }

    /**
     * Initialize health metrics tracking
     * 
     * @return void
     */
    private function initializeHealthMetrics(): void
    {
        $this->healthMetrics = [
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'average_execution_time' => 0.0,
            'total_mutations' => 0,
            'last_health_check' => microtime(true),
            'error_rate' => 0.0,
            'efficiency_score' => 1.0
        ];
    }

    /**
     * Update health metrics after execution
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
    }






    /**
     * Handle unit cleanup before shutdown
     * 
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @return void
     */
    public function shutdown(SemanticMeshInterface $mesh): void
    {
        $this->recordStigmergicTrace('unit_shutdown', [
            'final_health_metrics' => $this->getHealthMetrics(),
            'execution_history_count' => count($this->executionHistory),
            'total_runtime' => microtime(true) - ($this->executionHistory[0]['timestamp'] ?? microtime(true))
        ]);

        $this->recordExecution('shutdown', [
            'final_state' => 'clean_shutdown',
            'metrics_preserved' => true
        ]);
    }
}
