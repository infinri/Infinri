<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Exceptions;

/**
 * EthicalValidationException - Ethical Boundary Violation
 * 
 * Thrown when content or actions fail ethical validation checks.
 * Implements consciousness-level ethical reasoning with detailed analysis.
 * 
 * @architecture Ethical consciousness protection
 * @reference infinri_blueprint.md → FR-CORE-041 (Ethical Boundaries)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class EthicalValidationException extends SwarmException
{
    /**
     * Create ethical validation exception
     * 
     * @param float $ethicalScore Ethical score (0.0 to 1.0)
     * @param array $violations Specific ethical violations
     * @param string $unitId Unit ID that triggered the violation
     */
    public function __construct(
        float $ethicalScore, 
        array $violations,
        string $unitId = ''
    ) {
        $message = "Content failed ethical validation (score: {$ethicalScore})";
        
        $context = [
            'ethical_score' => $ethicalScore,
            'violations' => $violations,
            'requires_human_review' => $ethicalScore < 0.5,
            'severity_level' => $this->determineSeverityLevel($ethicalScore),
            'recommended_action' => $this->getRecommendedAction($ethicalScore)
        ];
        
        parent::__construct($message, 422, null, $context, $unitId);
    }

    /**
     * Get the ethical score
     * 
     * @return float Ethical score
     */
    public function getEthicalScore(): float
    {
        return $this->context['ethical_score'];
    }

    /**
     * Get specific violations
     * 
     * @return array Violations
     */
    public function getViolations(): array
    {
        return $this->context['violations'];
    }

    /**
     * Check if human review is required
     * 
     * @return bool True if human review required
     */
    public function requiresHumanReview(): bool
    {
        return $this->context['requires_human_review'];
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
     * Determine severity level based on ethical score
     * 
     * @param float $score Ethical score
     * @return string Severity level
     */
    private function determineSeverityLevel(float $score): string
    {
        return match(true) {
            $score < 0.3 => 'critical',
            $score < 0.5 => 'high',
            $score < 0.7 => 'medium',
            default => 'low'
        };
    }

    /**
     * Get recommended action based on ethical score
     * 
     * @param float $score Ethical score
     * @return string Recommended action
     */
    private function getRecommendedAction(float $score): string
    {
        return match(true) {
            $score < 0.3 => 'immediate_block',
            $score < 0.5 => 'human_review_required',
            $score < 0.7 => 'content_modification_suggested',
            default => 'warning_logged'
        };
    }
}
