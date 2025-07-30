<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

use Infinri\SwarmFramework\Exceptions\FallbackDepthExceededException;

/**
 * Fallback Management Trait
 * 
 * Provides intelligent fallback strategies with depth control and emergency handling.
 * Implements FR-CORE-036 fallback depth control with consciousness-level awareness.
 * 
 * @architecture Fallback strategy management
 * @reference infinri_blueprint.md → FR-CORE-036 (Fallback Depth Control)
 * @author Infinri Framework
 * @version 1.0.0
 */
trait FallbackManagementTrait
{
    private int $fallbackDepth = 0;
    private const MAX_FALLBACK_DEPTH = 3;

    /**
     * Intelligent fallback with depth control
     * Implements FR-CORE-036 fallback depth control
     * 
     * @param string $reason Fallback reason
     * @param int $currentDepth Current fallback depth
     * @return void
     * @throws FallbackDepthExceededException If max depth exceeded
     */
    protected function handleFallbackStrategy(string $reason, int $currentDepth = 0): void
    {
        $this->fallbackDepth = $currentDepth;
        
        if ($currentDepth >= self::MAX_FALLBACK_DEPTH) {
            $this->activateEmergencyFallback($reason);
            throw new FallbackDepthExceededException(
                $currentDepth, // depth (int)
                $reason, // strategy (string)
                'fallback-trait' // unit ID (string)
            );
        }
        
        $this->recordFallback($reason, $currentDepth);
        $this->triggerFallbackUnit($reason, $currentDepth + 1);
    }

    /**
     * Record fallback event for monitoring
     * 
     * @param string $reason Fallback reason
     * @param int $depth Fallback depth
     * @return void
     */
    private function recordFallback(string $reason, int $depth): void
    {
        $this->recordExecution('fallback_triggered', [
            'reason' => $reason,
            'depth' => $depth,
            'timestamp' => microtime(true)
        ]);
    }

    /**
     * Trigger fallback unit execution
     * 
     * @param string $reason Fallback reason
     * @param int $depth Fallback depth
     * @return void
     */
    private function triggerFallbackUnit(string $reason, int $depth): void
    {
        // Implementation would trigger appropriate fallback unit
        // This is a placeholder for the fallback mechanism
        $this->recordStigmergicTrace('fallback_unit_triggered', [
            'reason' => $reason,
            'depth' => $depth
        ]);
    }

    /**
     * Activate emergency fallback when max depth exceeded
     * 
     * @param string $reason Emergency reason
     * @return void
     */
    private function activateEmergencyFallback(string $reason): void
    {
        $this->recordExecution('emergency_fallback', [
            'reason' => $reason,
            'timestamp' => microtime(true)
        ]);
        
        $this->recordStigmergicTrace('emergency_fallback_activated', [
            'reason' => $reason,
            'unit_health' => $this->getHealthMetrics()
        ]);
    }

    /**
     * Get current fallback depth
     * 
     * @return int Current fallback depth
     */
    protected function getFallbackDepth(): int
    {
        return $this->fallbackDepth;
    }

    /**
     * Reset fallback depth (typically after successful execution)
     * 
     * @return void
     */
    protected function resetFallbackDepth(): void
    {
        $this->fallbackDepth = 0;
    }
}
