<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Threshold Validator - Centralized threshold checking
 * 
 * Eliminates redundant threshold validation patterns and provides
 * consistent threshold checking with automatic logging and alerts.
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class ThresholdValidator
{
    use LoggerTrait;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('ThresholdValidator', $config);
    }

    /**
     * Validate value against single threshold
     */
    public function validateThreshold(
        string $component,
        string $metric,
        float $value,
        float $threshold,
        string $operator = '>='
    ): array {
        $violated = match ($operator) {
            '>=' => $value >= $threshold,
            '>' => $value > $threshold,
            '<=' => $value <= $threshold,
            '<' => $value < $threshold,
            '==' => $value == $threshold,
            '!=' => $value != $threshold,
            default => false
        };

        $result = [
            'component' => $component,
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
            'operator' => $operator,
            'violated' => $violated,
            'timestamp' => PerformanceTimer::now()
        ];

        if ($violated) {
            $this->logger->warning("Threshold violated for {$component}.{$metric}", $result);
        }

        return $result;
    }

    /**
     * Validate value against warning and critical thresholds
     */
    public function validateMultiThreshold(
        string $component,
        string $metric,
        float $value,
        float $warningThreshold,
        float $criticalThreshold,
        string $operator = '>='
    ): array {
        $criticalViolated = match ($operator) {
            '>=' => $value >= $criticalThreshold,
            '>' => $value > $criticalThreshold,
            '<=' => $value <= $criticalThreshold,
            '<' => $value < $criticalThreshold,
            default => false
        };

        $warningViolated = match ($operator) {
            '>=' => $value >= $warningThreshold,
            '>' => $value > $warningThreshold,
            '<=' => $value <= $warningThreshold,
            '<' => $value < $warningThreshold,
            default => false
        };

        $status = 'normal';
        $level = 'info';

        if ($criticalViolated) {
            $status = 'critical';
            $level = 'critical';
        } elseif ($warningViolated) {
            $status = 'warning';
            $level = 'warning';
        }

        $result = [
            'component' => $component,
            'metric' => $metric,
            'value' => $value,
            'warning_threshold' => $warningThreshold,
            'critical_threshold' => $criticalThreshold,
            'operator' => $operator,
            'status' => $status,
            'warning_violated' => $warningViolated,
            'critical_violated' => $criticalViolated,
            'timestamp' => PerformanceTimer::now()
        ];

        if ($status !== 'normal') {
            $this->logger->log($level, "Threshold {$status} for {$component}.{$metric}", $result);
        }

        return $result;
    }

    /**
     * Validate multiple metrics against their thresholds
     */
    public function validateMetrics(
        string $component,
        array $metrics,
        array $thresholds
    ): array {
        $results = [];

        foreach ($metrics as $metric => $value) {
            if (!isset($thresholds[$metric])) {
                continue;
            }

            $threshold = $thresholds[$metric];

            if (is_array($threshold)) {
                // Multi-threshold validation
                $warning = $threshold['warning'] ?? $threshold['warn'] ?? null;
                $critical = $threshold['critical'] ?? $threshold['crit'] ?? null;
                $operator = $threshold['operator'] ?? '>=';

                if ($warning !== null && $critical !== null) {
                    $results[$metric] = $this->validateMultiThreshold(
                        $component,
                        $metric,
                        (float)$value,
                        (float)$warning,
                        (float)$critical,
                        $operator
                    );
                } elseif ($critical !== null) {
                    $results[$metric] = $this->validateThreshold(
                        $component,
                        $metric,
                        (float)$value,
                        (float)$critical,
                        $operator
                    );
                }
            } else {
                // Single threshold validation
                $results[$metric] = $this->validateThreshold(
                    $component,
                    $metric,
                    (float)$value,
                    (float)$threshold
                );
            }
        }

        return $results;
    }

    /**
     * Validate capacity usage with automatic threshold calculation
     */
    public function validateCapacity(
        string $component,
        string $resource,
        int $current,
        int $maximum,
        ?float $warningThreshold = null,
        ?float $criticalThreshold = null
    ): array {
        $warningThreshold ??= $this->config['default_warning_threshold'] ?? 0.8;
        $criticalThreshold ??= $this->config['default_critical_threshold'] ?? 0.95;

        $warningLimit = (int)($maximum * $warningThreshold);
        $criticalLimit = (int)($maximum * $criticalThreshold);

        return $this->validateMultiThreshold(
            $component,
            "{$resource}_capacity",
            (float)$current,
            (float)$warningLimit,
            (float)$criticalLimit,
            '>='
        );
    }

    /**
     * Validate performance metrics
     */
    public function validatePerformance(
        string $component,
        array $performanceMetrics
    ): array {
        $defaultThresholds = [
            'response_time' => ['warning' => 1.0, 'critical' => 5.0, 'operator' => '>='],
            'error_rate' => ['warning' => 5.0, 'critical' => 10.0, 'operator' => '>='],
            'hit_rate' => ['warning' => 80.0, 'critical' => 50.0, 'operator' => '<='],
            'throughput' => ['warning' => 100.0, 'critical' => 50.0, 'operator' => '<='],
            'cpu_usage' => ['warning' => 80.0, 'critical' => 95.0, 'operator' => '>='],
            'memory_usage' => ['warning' => 80.0, 'critical' => 95.0, 'operator' => '>=']
        ];

        $thresholds = array_merge($defaultThresholds, $this->config['performance_thresholds'] ?? []);

        return $this->validateMetrics($component, $performanceMetrics, $thresholds);
    }

    /**
     * Check if any thresholds are violated in results
     */
    public function hasViolations(array $validationResults): bool
    {
        foreach ($validationResults as $result) {
            if (isset($result['violated']) && $result['violated']) {
                return true;
            }
            if (isset($result['status']) && in_array($result['status'], ['warning', 'critical'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get violation summary from validation results
     */
    public function getViolationSummary(array $validationResults): array
    {
        $summary = [
            'total_checks' => count($validationResults),
            'violations' => 0,
            'warnings' => 0,
            'critical' => 0,
            'normal' => 0,
            'violated_metrics' => []
        ];

        foreach ($validationResults as $metric => $result) {
            if (isset($result['status'])) {
                switch ($result['status']) {
                    case 'critical':
                        $summary['critical']++;
                        $summary['violations']++;
                        $summary['violated_metrics'][] = $metric;
                        break;
                    case 'warning':
                        $summary['warnings']++;
                        $summary['violations']++;
                        $summary['violated_metrics'][] = $metric;
                        break;
                    default:
                        $summary['normal']++;
                }
            } elseif (isset($result['violated']) && $result['violated']) {
                $summary['violations']++;
                $summary['violated_metrics'][] = $metric;
            } else {
                $summary['normal']++;
            }
        }

        return $summary;
    }

    /**
     * Create alert from threshold violation
     */
    public function createAlert(array $validationResult): array
    {
        $severity = 'info';
        
        if (isset($validationResult['status'])) {
            $severity = match ($validationResult['status']) {
                'critical' => 'critical',
                'warning' => 'warning',
                default => 'info'
            };
        } elseif (isset($validationResult['violated']) && $validationResult['violated']) {
            $severity = 'warning';
        }

        return [
            'type' => 'threshold_violation',
            'severity' => $severity,
            'component' => $validationResult['component'] ?? 'unknown',
            'metric' => $validationResult['metric'] ?? 'unknown',
            'message' => $this->generateAlertMessage($validationResult),
            'details' => $validationResult,
            'timestamp' => PerformanceTimer::now(),
            'alert_id' => uniqid('alert_', true)
        ];
    }

    /**
     * Generate human-readable alert message
     */
    private function generateAlertMessage(array $validationResult): string
    {
        $component = $validationResult['component'] ?? 'Unknown component';
        $metric = $validationResult['metric'] ?? 'unknown metric';
        $value = $validationResult['value'] ?? 'unknown';

        if (isset($validationResult['status'])) {
            $status = $validationResult['status'];
            $threshold = match ($status) {
                'critical' => $validationResult['critical_threshold'] ?? 'unknown',
                'warning' => $validationResult['warning_threshold'] ?? 'unknown',
                default => 'unknown'
            };

            return "{$component} {$metric} is {$status}: {$value} (threshold: {$threshold})";
        }

        if (isset($validationResult['violated']) && $validationResult['violated']) {
            $threshold = $validationResult['threshold'] ?? 'unknown';
            $operator = $validationResult['operator'] ?? '>=';
            
            return "{$component} {$metric} threshold violated: {$value} {$operator} {$threshold}";
        }

        return "{$component} {$metric}: {$value}";
    }

    /**
     * Validate time-based thresholds (rate limiting)
     */
    public function validateRate(
        string $component,
        string $operation,
        int $count,
        int $timeWindowSeconds,
        int $maxOperations
    ): array {
        $rate = $timeWindowSeconds > 0 ? $count / $timeWindowSeconds : 0;
        $maxRate = $timeWindowSeconds > 0 ? $maxOperations / $timeWindowSeconds : 0;

        $violated = $count > $maxOperations;

        $result = [
            'component' => $component,
            'operation' => $operation,
            'count' => $count,
            'time_window' => $timeWindowSeconds,
            'max_operations' => $maxOperations,
            'rate' => round($rate, 2),
            'max_rate' => round($maxRate, 2),
            'violated' => $violated,
            'timestamp' => PerformanceTimer::now()
        ];

        if ($violated) {
            $this->logger->warning("Rate limit exceeded for {$component}.{$operation}", $result);
        }

        return $result;
    }
}
