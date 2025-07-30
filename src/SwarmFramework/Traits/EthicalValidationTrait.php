<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

use Infinri\SwarmFramework\Core\Validation\EthicalValidator;

/**
 * EthicalValidationTrait - Ethical Consciousness Validation
 * 
 * Provides consciousness-level ethical validation capabilities to SwarmUnits.
 * Implements ethical reasoning with toxicity, bias, and manipulation detection.
 * 
 * @architecture Reusable ethical consciousness behaviors
 * @reference infinri_blueprint.md → FR-CORE-041 (Ethical Boundaries)
 * @reference swarm_framework_pattern_blueprint.md → Validation Mesh Pattern
 * @author Infinri Framework
 * @version 1.0.0
 */
trait EthicalValidationTrait
{
    protected EthicalValidator $ethics;

    /**
     * Validate content ethics with consciousness-level reasoning
     * 
     * @param array $content Content to validate
     * @return float Ethical score (0.0 to 1.0)
     */
    protected function validateContentEthics(array $content): float
    {
        return $this->ethics->validateContent($content, [
            'toxicity_threshold' => 0.7,
            'bias_threshold' => 0.6,
            'manipulation_threshold' => 0.8,
            'hate_speech_threshold' => 0.9,
            'misinformation_threshold' => 0.8
        ]);
    }

    /**
     * Check if content is ethically acceptable
     * 
     * @param array $content Content to check
     * @param float $threshold Minimum acceptable score (default: 0.7)
     * @return bool True if ethically acceptable
     */
    protected function isEthicallyAcceptable(array $content, float $threshold = 0.7): bool
    {
        $score = $this->validateContentEthics($content);
        return $score >= $threshold;
    }

    /**
     * Get detailed ethical analysis
     * 
     * @param array $content Content to analyze
     * @return array Detailed ethical analysis
     */
    protected function getEthicalAnalysis(array $content): array
    {
        return $this->ethics->getDetailedAnalysis($content);
    }

    /**
     * Check for specific ethical violations
     * 
     * @param array $content Content to check
     * @return array List of ethical violations
     */
    protected function getEthicalViolations(array $content): array
    {
        $analysis = $this->getEthicalAnalysis($content);
        $violations = [];

        if ($analysis['toxicity_score'] > 0.7) {
            $violations[] = 'toxicity';
        }

        if ($analysis['bias_score'] > 0.6) {
            $violations[] = 'bias';
        }

        if ($analysis['manipulation_score'] > 0.8) {
            $violations[] = 'manipulation';
        }

        if ($analysis['hate_speech_score'] > 0.9) {
            $violations[] = 'hate_speech';
        }

        if ($analysis['misinformation_score'] > 0.8) {
            $violations[] = 'misinformation';
        }

        return $violations;
    }

    /**
     * Sanitize content to improve ethical score
     * 
     * @param array $content Content to sanitize
     * @return array Sanitized content
     */
    protected function sanitizeContent(array $content): array
    {
        return $this->ethics->sanitizeContent($content);
    }

    /**
     * Check if content requires human review
     * 
     * @param array $content Content to check
     * @return bool True if human review required
     */
    protected function requiresHumanReview(array $content): bool
    {
        $score = $this->validateContentEthics($content);
        return $score < 0.5;
    }

    /**
     * Get ethical recommendation for content
     * 
     * @param array $content Content to analyze
     * @return string Recommendation (approve/modify/reject/review)
     */
    protected function getEthicalRecommendation(array $content): string
    {
        $score = $this->validateContentEthics($content);

        return match(true) {
            $score >= 0.8 => 'approve',
            $score >= 0.7 => 'approve_with_monitoring',
            $score >= 0.5 => 'modify_suggested',
            $score >= 0.3 => 'human_review_required',
            default => 'reject'
        };
    }

    /**
     * Record ethical validation for consciousness tracking
     * 
     * @param array $content Content that was validated
     * @param float $score Ethical score
     * @param array $violations Any violations found
     * @return void
     */
    protected function recordEthicalValidation(array $content, float $score, array $violations): void
    {
        // Create scores array with violations as negative indicators
        $scores = [
            'overall' => $score,
            'violations' => $violations,
            'unit_id' => $this->getIdentity()->id
        ];
        
        $this->ethics->recordValidation($content, $scores, $score);
    }

    /**
     * Check if unit has ethical override privileges
     * 
     * @return bool True if unit can override ethical restrictions
     */
    protected function hasEthicalOverridePrivileges(): bool
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];
        return in_array('ethical_override', $unitCapabilities) || 
               in_array('admin', $unitCapabilities);
    }

    /**
     * Get ethical context for consciousness monitoring
     * 
     * @return array Ethical context
     */
    protected function getEthicalContext(): array
    {
        return [
            'unit_id' => $this->getIdentity()->id,
            'ethical_level' => $this->getUnitEthicalLevel(),
            'override_privileges' => $this->hasEthicalOverridePrivileges(),
            'validation_history' => $this->ethics->getValidationHistory($this->getIdentity()->id),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get unit's ethical level based on capabilities
     * 
     * @return string Ethical level (strict/standard/permissive)
     */
    protected function getUnitEthicalLevel(): string
    {
        $unitCapabilities = $this->getIdentity()->capabilities ?? [];

        if (in_array('ethical_strict', $unitCapabilities)) {
            return 'strict';
        }

        if (in_array('ethical_permissive', $unitCapabilities)) {
            return 'permissive';
        }

        return 'standard';
    }
}
