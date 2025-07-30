<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Exceptions;

/**
 * EntropyLimitExceededException - System Entropy Threshold Violation
 * 
 * Thrown when system entropy exceeds safe operational thresholds.
 * Implements consciousness-level entropy monitoring with auto-pruning triggers.
 * 
 * @architecture Entropy consciousness protection
 * @reference infinri_blueprint.md → FR-CORE-028 (Health Monitoring)
 * @tactic TAC-ENTROPY-001 (Entropy monitoring with auto-pruning)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class EntropyLimitExceededException extends SwarmException
{
    /**
     * Create entropy limit exceeded exception
     * 
     * @param float $currentEntropy Current system entropy level
     * @param float $threshold Entropy threshold that was exceeded
     * @param string $unitId Unit ID that triggered the violation
     */
    public function __construct(
        float $currentEntropy, 
        float $threshold,
        string $unitId = ''
    ) {
        $message = "System entropy exceeded threshold: {$currentEntropy} > {$threshold}";
        
        $context = [
            'current_entropy' => $currentEntropy,
            'threshold' => $threshold,
            'entropy_ratio' => $currentEntropy / $threshold,
            'auto_pruning_triggered' => true,
            'severity_level' => $this->determineSeverityLevel($currentEntropy, $threshold),
            'recommended_actions' => $this->getRecommendedActions($currentEntropy, $threshold)
        ];
        
        parent::__construct($message, 503, null, $context, $unitId);
    }

    /**
     * Get current entropy level
     * 
     * @return float Current entropy
     */
    public function getCurrentEntropy(): float
    {
        return $this->context['current_entropy'];
    }

    /**
     * Get entropy threshold
     * 
     * @return float Entropy threshold
     */
    public function getThreshold(): float
    {
        return $this->context['threshold'];
    }

    /**
     * Get entropy ratio (current/threshold)
     * 
     * @return float Entropy ratio
     */
    public function getEntropyRatio(): float
    {
        return $this->context['entropy_ratio'];
    }

    /**
     * Check if auto-pruning was triggered
     * 
     * @return bool True if auto-pruning triggered
     */
    public function isAutoPruningTriggered(): bool
    {
        return $this->context['auto_pruning_triggered'];
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
     * Determine severity level based on entropy ratio
     * 
     * @param float $current Current entropy
     * @param float $threshold Entropy threshold
     * @return string Severity level
     */
    private function determineSeverityLevel(float $current, float $threshold): string
    {
        $ratio = $current / $threshold;
        
        return match(true) {
            $ratio > 2.0 => 'critical',
            $ratio > 1.5 => 'high',
            $ratio > 1.2 => 'medium',
            default => 'low'
        };
    }

    /**
     * Get recommended actions based on entropy level
     * 
     * @param float $current Current entropy
     * @param float $threshold Entropy threshold
     * @return array Recommended actions
     */
    private function getRecommendedActions(float $current, float $threshold): array
    {
        $ratio = $current / $threshold;
        
        $actions = ['auto_prune_mesh_data'];
        
        if ($ratio > 1.5) {
            $actions[] = 'throttle_unit_execution';
            $actions[] = 'clear_execution_history';
        }
        
        if ($ratio > 2.0) {
            $actions[] = 'emergency_mesh_cleanup';
            $actions[] = 'restart_reactor_engine';
        }
        
        return $actions;
    }
}
