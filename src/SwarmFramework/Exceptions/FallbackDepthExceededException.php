<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Exceptions;

/**
 * FallbackDepthExceededException - Fallback Strategy Depth Violation
 * 
 * Thrown when fallback strategy depth exceeds safe operational limits.
 * Implements consciousness-level fallback control with emergency brake activation.
 * 
 * @architecture Fallback consciousness protection
 * @reference infinri_blueprint.md → FR-CORE-036 (Fallback Depth Control)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class FallbackDepthExceededException extends SwarmException
{
    /**
     * Create fallback depth exceeded exception
     * 
     * @param int $depth Current fallback depth
     * @param string $strategy Fallback strategy being used
     * @param string $unitId Unit ID that triggered the violation
     */
    public function __construct(
        int $depth, 
        string $strategy,
        string $unitId = ''
    ) {
        $message = "Fallback depth exceeded: {$depth} levels in strategy {$strategy}";
        
        $context = [
            'fallback_depth' => $depth,
            'strategy' => $strategy,
            'max_allowed_depth' => 3,
            'emergency_brake_activated' => true,
            'severity_level' => $this->determineSeverityLevel($depth),
            'recovery_actions' => $this->getRecoveryActions($depth, $strategy)
        ];
        
        parent::__construct($message, 500, null, $context, $unitId);
    }

    /**
     * Get fallback depth
     * 
     * @return int Fallback depth
     */
    public function getFallbackDepth(): int
    {
        return $this->context['fallback_depth'];
    }

    /**
     * Get fallback strategy
     * 
     * @return string Fallback strategy
     */
    public function getStrategy(): string
    {
        return $this->context['strategy'];
    }

    /**
     * Get maximum allowed depth
     * 
     * @return int Maximum allowed depth
     */
    public function getMaxAllowedDepth(): int
    {
        return $this->context['max_allowed_depth'];
    }

    /**
     * Check if emergency brake was activated
     * 
     * @return bool True if emergency brake activated
     */
    public function isEmergencyBrakeActivated(): bool
    {
        return $this->context['emergency_brake_activated'];
    }

    /**
     * Get severity level
     * 
     * @return string Severity level
     */
    public function getSeverityLevel(): string
    {
        return $this->context['severity_level'];
    }



    /**
     * Determine severity level based on fallback depth
     * 
     * @param int $depth Fallback depth
     * @return string Severity level
     */
    private function determineSeverityLevel(int $depth): string
    {
        return match(true) {
            $depth > 5 => 'critical',
            $depth > 3 => 'high',
            default => 'medium'
        };
    }

    /**
     * Get recovery actions based on depth and strategy
     * 
     * @param int $depth Fallback depth
     * @param string $strategy Fallback strategy
     * @return array Recovery actions
     */
    private function getRecoveryActions(int $depth, string $strategy): array
    {
        $actions = ['emergency_stop', 'reset_fallback_chain'];
        
        if ($depth > 4) {
            $actions[] = 'restart_unit';
            $actions[] = 'clear_execution_state';
        }
        
        if ($strategy === 'unit_unhealthy') {
            $actions[] = 'health_diagnostics';
            $actions[] = 'repair_unit_state';
        }
        
        return $actions;
    }
}
