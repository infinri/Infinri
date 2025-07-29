<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Reactor\UnitEvaluationEngine;
use Infinri\SwarmFramework\Core\Reactor\MutexResolutionEngine;
use Infinri\SwarmFramework\Core\Reactor\ExecutionMonitor;
use Infinri\SwarmFramework\Core\Reactor\ThrottlingController;
use Infinri\SwarmFramework\Core\Reactor\ReactorTickResult;
use Infinri\SwarmFramework\Core\Safety\SafetyLimitsEnforcer;
use Infinri\SwarmFramework\Core\Safety\RuntimeValidator;
use Psr\Log\LoggerInterface;

/**
 * Swarm Reactor - The Neural Network Execution Engine
 * 
 * Coordinates SwarmUnit execution by delegating specialized tasks
 * to focused engines. Maintains the reactor loop pattern while
 * ensuring modularity and maintainability.
 * 
 * @performance 20k units/tick in 50ms via RoadRunner workers
 * @architecture Implements Reactor Loop Pattern with specialized engines
 * @reference infinri_blueprint.md → FR-CORE-001
 * @tactic TAC-SCAL-001 (Priority-based execution with adaptive throttling)
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: [
    'SemanticMeshInterface', 'LoggerInterface', 'UnitEvaluationEngine', 
    'MutexResolutionEngine', 'ExecutionMonitor', 'ThrottlingController',
    'SafetyLimitsEnforcer', 'RuntimeValidator'
])]
final class SwarmReactor
{
    use LoggerTrait;
    private SemanticMeshInterface $mesh;
    private UnitEvaluationEngine $evaluationEngine;
    private MutexResolutionEngine $mutexEngine;
    private ExecutionMonitor $executionMonitor;
    private ThrottlingController $throttlingController;
    private SafetyLimitsEnforcer $safetyEnforcer;
    private RuntimeValidator $validator;
    
    private array $registeredUnits = [];
    private array $unitCooldowns = [];
    private array $mutexGroups = [];
    private int $tickCounter = 0;
    private float $lastTickTime = 0;
    private array $config;

    /**
     * @param SemanticMeshInterface $mesh The semantic mesh
     * @param LoggerInterface $logger Logger instance
     * @param UnitEvaluationEngine $evaluationEngine Unit evaluation engine
     * @param MutexResolutionEngine $mutexEngine Mutex resolution engine
     * @param ExecutionMonitor $executionMonitor Execution monitoring engine
     * @param ThrottlingController $throttlingController Throttling controller
     * @param SafetyLimitsEnforcer $safetyEnforcer Safety limits enforcer
     * @param RuntimeValidator $validator Runtime validator
     * @param array $config Reactor configuration
     */
    public function __construct(
        SemanticMeshInterface $mesh,
        LoggerInterface $logger,
        UnitEvaluationEngine $evaluationEngine,
        MutexResolutionEngine $mutexEngine,
        ExecutionMonitor $executionMonitor,
        ThrottlingController $throttlingController,
        SafetyLimitsEnforcer $safetyEnforcer,
        RuntimeValidator $validator,
        array $config = []
    ) {
        $this->mesh = $mesh;
        $this->logger = $logger;
        $this->evaluationEngine = $evaluationEngine;
        $this->mutexEngine = $mutexEngine;
        $this->executionMonitor = $executionMonitor;
        $this->throttlingController = $throttlingController;
        $this->safetyEnforcer = $safetyEnforcer;
        $this->validator = $validator;
        $this->config = ConfigManager::getConfig('SwarmReactor', $config);
    }

    /**
     * Execute a single reactor tick
     * 
     * This is the main execution loop that coordinates all specialized engines
     * to evaluate, resolve conflicts, and execute SwarmUnits efficiently.
     * 
     * @return ReactorTickResult Execution results and metrics
     * @throws ReactorException If tick execution fails critically
     */
    public function executeReactorTick(): ReactorTickResult
    {
        $tickStart = PerformanceTimer::now();
        $this->tickCounter++;

        try {
            // 1. Create snapshot isolation for consistent evaluation
            $snapshot = $this->createMeshSnapshot();
            
            // 2. Delegate unit evaluation to specialized engine
            $triggeredUnits = $this->evaluationEngine->evaluateUnits(
                $this->registeredUnits, 
                $snapshot, 
                $this->unitCooldowns
            );
            
            // 3. Delegate mutex collision resolution to specialized engine
            $prioritizedUnits = $this->mutexEngine->resolveMutexCollisions(
                $triggeredUnits, 
                $this->mutexGroups
            );
            
            // 4. Delegate execution monitoring to specialized engine
            $executionResults = $this->executionMonitor->executeUnitsWithMonitoring(
                $prioritizedUnits, 
                $this->mesh
            );
            
            // 5. Update cooldowns for executed units
            $this->updateUnitCooldowns($prioritizedUnits);
            
            // 6. Update mutex group states
            $this->updateMutexGroupStates($prioritizedUnits);
            
            // 7. Delegate adaptive throttling to specialized controller
            $healthMetrics = $this->executionMonitor->getHealthMetrics();
            $this->throttlingController->adjustThrottling($tickStart, $healthMetrics);

            $tickDuration = PerformanceTimer::now() - $tickStart;
            $this->lastTickTime = $tickDuration;

            $result = new ReactorTickResult(
                tickId: $this->tickCounter,
                duration: $tickDuration,
                unitsEvaluated: count($this->registeredUnits),
                unitsTriggered: count($triggeredUnits),
                unitsExecuted: count($prioritizedUnits),
                unitsFailed: count(array_filter($executionResults, fn($r) => !$r['success'])),
                meshSnapshot: $snapshot,
                executionResults: $executionResults
            );

            $this->logger->info('Reactor tick completed', $result->toArray());

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Reactor tick failed', [
                'tick_id' => $this->tickCounter,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ReactorException(
                "Reactor tick {$this->tickCounter} failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Register a SwarmUnit for execution
     */
    public function registerUnit(SwarmUnitInterface $unit): bool
    {
        try {
            $identity = $unit->getIdentity();
            $unitId = $identity->id;

            // Validate unit before registration
            $this->validator->validateUnitIdentity($identity);

            // Check if already registered
            if (isset($this->registeredUnits[$unitId])) {
                $this->logger->warning('Unit already registered', $this->buildOperationContext('unit_registration', ['unit_id' => $unitId, 'status' => 'already_exists']));
                return false;
            }

            // Safety checks before registration
            $this->safetyEnforcer->checkExecutionStart($unitId);

            // Register the unit
            $this->registeredUnits[$unitId] = $unit;

            $this->logger->info('Unit registered successfully', [
                'unit_id' => $unitId,
                'version' => $identity->version,
                'capabilities' => $identity->capabilities,
                'total_registered_units' => count($this->registeredUnits)
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Unit registration failed', [
                'unit_id' => $unit->getIdentity()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unregister a SwarmUnit
     */
    public function unregisterUnit(string $unitId): bool
    {
        if (!isset($this->registeredUnits[$unitId])) {
            $this->logger->warning('Attempted to unregister non-existent unit', $this->buildOperationContext('unit_unregistration', ['unit_id' => $unitId, 'status' => 'not_found']));
            return false;
        }

        unset($this->registeredUnits[$unitId]);
        unset($this->unitCooldowns[$unitId]);

        $this->logger->info('Unit unregistered successfully', [
            'unit_id' => $unitId,
            'remaining_units' => count($this->registeredUnits)
        ]);

        return true;
    }

    /**
     * Get comprehensive reactor health and performance metrics
     */
    public function getHealthMetrics(): array
    {
        $executionMetrics = $this->executionMonitor->getHealthMetrics();
        $throttlingStats = $this->throttlingController->getThrottlingStats();
        $mutexStats = $this->mutexEngine->getResolutionStats($this->mutexGroups);
        $evaluationStats = $this->evaluationEngine->getEvaluationStats();

        return [
            'reactor' => [
                'tick_counter' => $this->tickCounter,
                'registered_units' => count($this->registeredUnits),
                'active_cooldowns' => count($this->unitCooldowns),
                'mutex_groups' => count($this->mutexGroups),
                'last_tick_duration' => $this->lastTickTime,
                'is_healthy' => $this->executionMonitor->isSystemHealthy()
            ],
            'execution' => $executionMetrics,
            'throttling' => $throttlingStats,
            'mutex_resolution' => $mutexStats,
            'evaluation' => $evaluationStats
        ];
    }

    /**
     * Create a consistent mesh snapshot for unit evaluation
     */
    private function createMeshSnapshot(): array
    {
        return $this->mesh->snapshot(['*']);
    }

    /**
     * Update unit cooldowns after execution
     */
    private function updateUnitCooldowns(array $executedUnits): void
    {
        $currentTime = PerformanceTimer::now();
        
        foreach ($executedUnits as $unitData) {
            $unit = $unitData['unit'];
            $unitId = $unit->getIdentity()->id;
            $cooldown = $unit->getCooldown();
            
            if ($cooldown > 0) {
                $this->unitCooldowns[$unitId] = $currentTime + $cooldown;
            }
        }

        // Clean up expired cooldowns
        $this->unitCooldowns = array_filter(
            $this->unitCooldowns, 
            fn($cooldownEnd) => $cooldownEnd > $currentTime
        );
    }

    /**
     * Update mutex group states after execution
     */
    private function updateMutexGroupStates(array $executedUnits): void
    {
        foreach ($executedUnits as $unitData) {
            $unit = $unitData['unit'];
            $mutexGroup = $unit->getMutexGroup();
            
            if ($mutexGroup !== null) {
                $this->mutexEngine->updateMutexGroupState(
                    $mutexGroup, 
                    $unit->getIdentity()->id, 
                    $this->mutexGroups
                );
            }
        }
    }

    /**
     * Get registered units count
     */
    public function getRegisteredUnitsCount(): int
    {
        return count($this->registeredUnits);
    }

    /**
     * Check if reactor is healthy
     */
    public function isHealthy(): bool
    {
        return $this->executionMonitor->isSystemHealthy() &&
               $this->throttlingController->getCurrentThrottleRate() > 0.5;
    }
}

/**
 * Exception thrown when reactor operations fail
 */
final class ReactorException extends \RuntimeException
{
}
