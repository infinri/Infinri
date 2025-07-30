<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Validation;

/**
 * Ethical Validator - Consciousness-Level Ethical Reasoning
 * 
 * Provides ethical validation with consciousness-level reasoning capabilities
 * including toxicity, bias, manipulation detection, and AI safety measures.
 * 
 * @architecture Ethical validation and consciousness reasoning
 * @reference infinri_blueprint.md → FR-CORE-041 (Ethical Boundaries)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class EthicalValidator
{
    private array $config;
    private array $validationHistory = [];

    /**
     * Initialize ethical validator
     * 
     * @param array $config Ethical validation configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'toxicity_threshold' => 0.7,
            'bias_threshold' => 0.6,
            'manipulation_threshold' => 0.8,
            'hate_speech_threshold' => 0.9,
            'misinformation_threshold' => 0.8,
            'ai_safety_enabled' => true,
            'human_review_threshold' => 0.5
        ], $config);
    }

    /**
     * Validate content for ethical compliance
     * 
     * @param array $content Content to validate
     * @return float Ethical score (0.0 to 1.0, higher is more ethical)
     */
    public function validateContent(array $content): float
    {
        $scores = [
            'toxicity' => $this->checkToxicity($content),
            'bias' => $this->checkBias($content),
            'manipulation' => $this->checkManipulation($content),
            'hate_speech' => $this->checkHateSpeech($content),
            'misinformation' => $this->checkMisinformation($content)
        ];
        
        $overallScore = array_sum($scores) / count($scores);
        
        $this->recordValidation($content, $scores, $overallScore);
        
        return $overallScore;
    }

    /**
     * Validate context for ethical compliance
     * 
     * @param array $context Context to validate
     * @return float Ethical score (0.0 to 1.0, higher is more ethical)
     */
    public function validateContext(array $context): float
    {
        return $this->validateContent($context);
    }

    /**
     * Check if content requires human review
     * 
     * @param array $content Content to check
     * @return bool True if human review is required
     */
    public function requiresHumanReview(array $content): bool
    {
        $score = $this->validateContent($content);
        return $score < $this->config['human_review_threshold'];
    }

    /**
     * Get ethical recommendations for content
     * 
     * @param array $content Content to analyze
     * @return array Ethical recommendations
     */
    public function getRecommendations(array $content): array
    {
        $score = $this->validateContent($content);
        $recommendations = [];
        
        if ($score < 0.5) {
            $recommendations[] = 'Content requires significant ethical review';
            $recommendations[] = 'Consider human oversight before proceeding';
        } elseif ($score < 0.7) {
            $recommendations[] = 'Content may benefit from ethical refinement';
            $recommendations[] = 'Monitor for potential bias or manipulation';
        }
        
        return $recommendations;
    }

    /**
     * Get validation history
     * 
     * @return array Validation history
     */
    public function getValidationHistory(): array
    {
        return $this->validationHistory;
    }

    /**
     * Check content for toxicity
     * 
     * @param array $content Content to check
     * @return float Toxicity score (0.0 = toxic, 1.0 = non-toxic)
     */
    private function checkToxicity(array $content): float
    {
        // Simplified toxicity detection
        $text = $this->extractText($content);
        $toxicWords = ['hate', 'kill', 'destroy', 'harm', 'abuse'];
        
        $toxicCount = 0;
        $totalWords = str_word_count($text);
        
        foreach ($toxicWords as $word) {
            $toxicCount += substr_count(strtolower($text), $word);
        }
        
        if ($totalWords === 0) return 1.0;
        
        $toxicityRatio = $toxicCount / $totalWords;
        return max(0.0, 1.0 - ($toxicityRatio * 10)); // Scale appropriately
    }

    /**
     * Check content for bias
     * 
     * @param array $content Content to check
     * @return float Bias score (0.0 = biased, 1.0 = unbiased)
     */
    private function checkBias(array $content): float
    {
        // Simplified bias detection
        $text = $this->extractText($content);
        $biasIndicators = ['always', 'never', 'all', 'none', 'every', 'no one'];
        
        $biasCount = 0;
        foreach ($biasIndicators as $indicator) {
            $biasCount += substr_count(strtolower($text), $indicator);
        }
        
        $totalWords = str_word_count($text);
        if ($totalWords === 0) return 1.0;
        
        $biasRatio = $biasCount / $totalWords;
        return max(0.0, 1.0 - ($biasRatio * 5));
    }

    /**
     * Check content for manipulation
     * 
     * @param array $content Content to check
     * @return float Manipulation score (0.0 = manipulative, 1.0 = non-manipulative)
     */
    private function checkManipulation(array $content): float
    {
        // Simplified manipulation detection
        $text = $this->extractText($content);
        $manipulativeWords = ['must', 'should', 'need to', 'have to', 'urgent'];
        
        $manipulativeCount = 0;
        foreach ($manipulativeWords as $word) {
            $manipulativeCount += substr_count(strtolower($text), $word);
        }
        
        $totalWords = str_word_count($text);
        if ($totalWords === 0) return 1.0;
        
        $manipulationRatio = $manipulativeCount / $totalWords;
        return max(0.0, 1.0 - ($manipulationRatio * 3));
    }

    /**
     * Check content for hate speech
     * 
     * @param array $content Content to check
     * @return float Hate speech score (0.0 = hate speech, 1.0 = no hate speech)
     */
    private function checkHateSpeech(array $content): float
    {
        // Simplified hate speech detection
        $text = $this->extractText($content);
        $hateSpeechWords = ['racist', 'sexist', 'bigot', 'supremacist'];
        
        foreach ($hateSpeechWords as $word) {
            if (stripos($text, $word) !== false) {
                return 0.0; // Immediate flag for hate speech
            }
        }
        
        return 1.0;
    }

    /**
     * Check content for misinformation
     * 
     * @param array $content Content to check
     * @return float Misinformation score (0.0 = misinformation, 1.0 = accurate)
     */
    private function checkMisinformation(array $content): float
    {
        // Simplified misinformation detection
        $text = $this->extractText($content);
        $misinformationIndicators = ['fake news', 'conspiracy', 'hoax', 'lie'];
        
        foreach ($misinformationIndicators as $indicator) {
            if (stripos($text, $indicator) !== false) {
                return 0.3; // Flag as potential misinformation
            }
        }
        
        return 0.8; // Default to mostly accurate
    }

    /**
     * Extract text from content array
     * 
     * @param array $content Content array
     * @return string Extracted text
     */
    private function extractText(array $content): string
    {
        $text = '';
        
        array_walk_recursive($content, function($value) use (&$text) {
            if (is_string($value)) {
                $text .= ' ' . $value;
            }
        });
        
        return trim($text);
    }

    /**
     * Get detailed analysis of content
     * 
     * @param array $content Content to analyze
     * @return array Detailed analysis results
     */
    public function getDetailedAnalysis(array $content): array
    {
        $scores = [
            'toxicity' => $this->checkToxicity($content),
            'bias' => $this->checkBias($content),
            'manipulation' => $this->checkManipulation($content),
            'hate_speech' => $this->checkHateSpeech($content),
            'misinformation' => $this->checkMisinformation($content)
        ];
        
        $overallScore = array_sum($scores) / count($scores);
        
        return [
            'overall_score' => $overallScore,
            'individual_scores' => $scores,
            'requires_review' => $overallScore < $this->config['human_review_threshold'],
            'recommendations' => $this->getRecommendations($content),
            'risk_level' => $this->determineRiskLevel($overallScore),
            'content_hash' => md5(serialize($content)),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Sanitize content based on ethical guidelines
     * 
     * @param array $content Content to sanitize
     * @return array Sanitized content
     */
    public function sanitizeContent(array $content): array
    {
        $sanitized = $content;
        
        // Apply basic sanitization rules
        array_walk_recursive($sanitized, function(&$value) {
            if (is_string($value)) {
                // Remove potentially harmful content
                $harmfulPatterns = [
                    '/\b(hate|kill|destroy|harm|abuse)\b/i',
                    '/\b(racist|sexist|bigot|supremacist)\b/i',
                    '/\b(fake news|conspiracy|hoax)\b/i'
                ];
                
                foreach ($harmfulPatterns as $pattern) {
                    $value = preg_replace($pattern, '[REDACTED]', $value);
                }
                
                // Basic HTML/script sanitization
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });
        
        return $sanitized;
    }

    /**
     * Determine risk level based on ethical score
     * 
     * @param float $score Ethical score
     * @return string Risk level
     */
    private function determineRiskLevel(float $score): string
    {
        return match(true) {
            $score < 0.3 => 'critical',
            $score < 0.5 => 'high',
            $score < 0.7 => 'medium',
            default => 'low'
        };
    }

    /**
     * Record validation result
     * 
     * @param array $content Content that was validated
     * @param array $scores Individual scores
     * @param float $overallScore Overall ethical score
     * @return void
     */
    public function recordValidation(array $content, array $scores, float $overallScore): void
    {
        $this->validationHistory[] = [
            'timestamp' => microtime(true),
            'content_hash' => md5(serialize($content)),
            'scores' => $scores,
            'overall_score' => $overallScore,
            'requires_review' => $overallScore < $this->config['human_review_threshold']
        ];
        
        // Limit history size
        if (count($this->validationHistory) > 1000) {
            array_shift($this->validationHistory);
        }
    }
}
