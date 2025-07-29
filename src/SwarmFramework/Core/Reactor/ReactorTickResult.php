<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

/**
 * Reactor Tick Execution Result
 * 
 * Encapsulates the results and metrics from a single reactor tick execution.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
final class ReactorTickResult
{
    public function __construct(
        public readonly int $tickId,
        public readonly float $duration,
        public readonly int $unitsEvaluated,
        public readonly int $unitsTriggered,
        public readonly int $unitsExecuted,
        public readonly int $unitsFailed,
        public readonly array $meshSnapshot,
        public readonly array $executionResults
    ) {}

    /**
     * Get success rate for this tick
     */
    public function getSuccessRate(): float
    {
        if ($this->unitsExecuted === 0) {
            return 1.0;
        }
        
        return ($this->unitsExecuted - $this->unitsFailed) / $this->unitsExecuted;
    }

    /**
     * Get units per second for this tick
     */
    public function getUnitsPerSecond(): float
    {
        if ($this->duration === 0.0) {
            return 0.0;
        }
        
        return $this->unitsExecuted / $this->duration;
    }

    /**
     * Check if tick was healthy
     */
    public function isHealthy(): bool
    {
        return $this->getSuccessRate() >= 0.95 && $this->duration <= 0.1; // 100ms threshold
    }

    /**
     * Convert to array for logging/serialization
     */
    public function toArray(): array
    {
        return [
            'tick_id' => $this->tickId,
            'duration' => $this->duration,
            'units_evaluated' => $this->unitsEvaluated,
            'units_triggered' => $this->unitsTriggered,
            'units_executed' => $this->unitsExecuted,
            'units_failed' => $this->unitsFailed,
            'success_rate' => $this->getSuccessRate(),
            'units_per_second' => $this->getUnitsPerSecond(),
            'is_healthy' => $this->isHealthy()
        ];
    }
}
