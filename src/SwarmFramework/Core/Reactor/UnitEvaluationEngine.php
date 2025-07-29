<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Safety\SafetyLimitsEnforcer;
use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Core\Validation\MeshDataValidator;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Psr\Log\LoggerInterface;

/**
 * Unit Evaluation Engine - SwarmUnit Triggering Logic
 * 
 * Evaluates registered SwarmUnits against mesh snapshots to determine
 * which units should be triggered for execution based on their conditions.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface', 'SafetyLimitsEnforcer', 'RuntimeValidator'])]
final class UnitEvaluationEngine
{
    use LoggerTrait;

    private SafetyLimitsEnforcer $safetyEnforcer;
    private MeshDataValidator $validator;
    private array $config;

    public function __construct(
        LoggerInterface $logger,
        SafetyLimitsEnforcer $safetyEnforcer,
        MeshDataValidator $validator,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->safetyEnforcer = $safetyEnforcer;
        $this->validator = $validator;
        $this->config = ConfigManager::getConfig('UnitEvaluationEngine', $config);
    }

    /**
     * Evaluate all registered units against the mesh snapshot
     */
    public function evaluateUnits(array $registeredUnits, array $snapshot, array $unitCooldowns): array
    {
        $triggeredUnits = [];
        $evaluationStart = PerformanceTimer::now();
        $unitsEvaluated = 0;

        foreach ($registeredUnits as $unitId => $unit) {
            // Check evaluation timeout
            if ((PerformanceTimer::now() - $evaluationStart) * 1000 > $this->config['evaluation_timeout_ms']) {
                $this->logger->warning('Unit evaluation timeout reached', $this->buildPerformanceContext('unit_evaluation', $evaluationStart, [
                    'timeout_ms' => $this->config['evaluation_timeout_ms'],
                    'units_processed' => $unitsEvaluated
                ]));
                break;
            }

            // Check safety limits
            try {
                $this->safetyEnforcer->checkExecutionStart($unitId);
            } catch (\Exception $e) {
                $this->logger->warning('Unit skipped due to safety limits', $this->buildSecurityContext('safety_violation', [
                    'unit_id' => $unit->getIdentity()->getId(),
                    'reason' => 'safety_violation'
                ]));
                continue;
            }

            // Check cooldown
            if ($this->isUnitOnCooldown($unitId, $unitCooldowns)) {
                continue;
            }

            // Validate unit before evaluation
            try {
                // Basic unit validation - ensure unit has valid identity
                $identity = $unit->getIdentity();
                if (empty($identity) || !is_string($identity)) {
                    throw ExceptionFactory::invalidArgument(
                        'Unit has invalid identity',
                        ['unit_class' => get_class($unit), 'identity' => $identity]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Unit validation failed', $this->buildValidationContext(
                    ['Unit validation failed'],
                    [],
                    [
                        'unit_id' => $unitId,
                        'unit_class' => get_class($unit)
                    ]
                ));
                continue;
            }

            // Evaluate trigger condition
            if ($this->evaluateUnitTrigger($unit, $snapshot)) {
                $triggeredUnits[] = [
                    'unit' => $unit,
                    'priority' => $unit->getPriority(),
                    'mutex_group' => $unit->getMutexGroup(),
                    'evaluation_time' => PerformanceTimer::now()
                ];
            }

            $unitsEvaluated++;

            // Safety check for max units per evaluation
            if ($unitsEvaluated >= $this->config['max_units_per_evaluation']) {
                $this->logger->warning('Max units per evaluation reached', $this->buildPerformanceContext('unit_evaluation_limit', $evaluationStart, [
                    'max_units' => $this->config['max_units_per_evaluation'],
                    'units_evaluated' => $unitsEvaluated
                ]));
                break;
            }
        }

        // Sort by priority (higher priority first)
        usort($triggeredUnits, fn($a, $b) => $b['priority'] <=> $a['priority']);

        $this->logger->debug('Unit evaluation completed', $this->buildPerformanceContext('unit_evaluation_completed', $evaluationStart, [
            'units_evaluated' => $unitsEvaluated,
            'units_triggered' => count($triggeredUnits),
            'evaluation_duration_ms' => (PerformanceTimer::now() - $evaluationStart) * 1000
        ]));

        return $triggeredUnits;
    }

    /**
     * Evaluate if a unit should be triggered
     */
    private function evaluateUnitTrigger(SwarmUnitInterface $unit, array $snapshot): bool
    {
        try {
            // Create a read-only mesh snapshot for the unit
            $meshSnapshot = new MeshSnapshot($snapshot);
            
            // Call the unit's trigger condition
            return $unit->triggerCondition($meshSnapshot);
        } catch (\Exception $e) {
            $this->logger->error('Unit trigger evaluation failed', [
                'unit_id' => $unit->getIdentity()->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if unit is on cooldown
     */
    private function isUnitOnCooldown(string $unitId, array $unitCooldowns): bool
    {
        if (!isset($unitCooldowns[$unitId])) {
            return false;
        }

        $cooldownEnd = $unitCooldowns[$unitId];
        return PerformanceTimer::now() < $cooldownEnd;
    }

    /**
     * Get evaluation statistics
     */
    public function getEvaluationStats(): array
    {
        return [
            'max_units_per_evaluation' => $this->config['max_units_per_evaluation'],
            'evaluation_timeout_ms' => $this->config['evaluation_timeout_ms'],
            'parallel_evaluation_enabled' => $this->config['enable_parallel_evaluation']
        ];
    }
}

/**
 * Mesh snapshot wrapper for unit evaluation
 */
final class MeshSnapshot implements \Infinri\SwarmFramework\Interfaces\SemanticMeshInterface
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get(string $key, ?string $namespace = null): mixed
    {
        $fullKey = $namespace ? "mesh.{$namespace}.{$key}" : "mesh.{$key}";
        return $this->data[$fullKey] ?? null;
    }

    // Read-only operations only - all write operations throw exceptions
    public function set(string $key, mixed $value, ?string $namespace = null): bool
    {
        throw new \RuntimeException('Mesh snapshots are read-only');
    }

    public function compareAndSet(string $key, mixed $expected, mixed $value): bool
    {
        throw new \RuntimeException('Mesh snapshots are read-only');
    }

    public function snapshot(array $keyPatterns = ['*']): array
    {
        return $this->data;
    }

    public function getVersion(string $key): int
    {
        return 1; // Snapshots have fixed version
    }

    public function subscribe(string $pattern, callable $callback): void
    {
        throw new \RuntimeException('Mesh snapshots do not support subscriptions');
    }

    public function publish(string $channel, array $data): void
    {
        throw new \RuntimeException('Mesh snapshots do not support publishing');
    }

    public function all(): array
    {
        return $this->data;
    }

    public function exists(string $key, ?string $namespace = null): bool
    {
        $fullKey = $namespace ? "mesh.{$namespace}.{$key}" : "mesh.{$key}";
        return isset($this->data[$fullKey]);
    }

    public function delete(string $key, ?string $namespace = null): bool
    {
        throw new \RuntimeException('Mesh snapshots are read-only');
    }

    public function getStats(): array
    {
        return ['keys' => count($this->data)];
    }

    public function clear(?string $namespace = null): bool
    {
        throw new \RuntimeException('Mesh snapshots are read-only');
    }
}
