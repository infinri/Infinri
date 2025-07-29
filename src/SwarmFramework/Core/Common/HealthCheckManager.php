<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Health Check Manager - Centralized health monitoring
 * 
 * Eliminates redundant health check patterns across the framework
 * and provides consistent threshold-based health assessment.
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class HealthCheckManager
{
    use LoggerTrait;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('HealthCheckManager', $config);
    }

    /**
     * Check threshold-based health with automatic logging
     */
    public function checkThresholdHealth(
        string $component,
        string $metric,
        float $current,
        float $maximum,
        ?float $warningThreshold = null,
        ?float $criticalThreshold = null
    ): array {
        $warningThreshold ??= $this->config['warning_threshold'] ?? 0.8;
        $criticalThreshold ??= $this->config['critical_threshold'] ?? 0.95;
        
        $ratio = $maximum > 0 ? $current / $maximum : 0;
        
        if ($ratio >= $criticalThreshold) {
            $this->logger->critical("{$component} {$metric} critical", [
                'component' => $component,
                'metric' => $metric,
                'current' => $current,
                'maximum' => $maximum,
                'ratio' => $ratio,
                'threshold' => $criticalThreshold
            ]);
            
            return [
                'status' => 'critical',
                'message' => "{$metric} limit exceeded",
                'ratio' => $ratio,
                'current' => $current,
                'maximum' => $maximum
            ];
        }
        
        if ($ratio >= $warningThreshold) {
            $this->logger->warning("{$component} {$metric} warning", [
                'component' => $component,
                'metric' => $metric,
                'current' => $current,
                'maximum' => $maximum,
                'ratio' => $ratio,
                'threshold' => $warningThreshold
            ]);
            
            return [
                'status' => 'warning',
                'message' => "{$metric} approaching limit",
                'ratio' => $ratio,
                'current' => $current,
                'maximum' => $maximum
            ];
        }
        
        return [
            'status' => 'healthy',
            'message' => "{$metric} within normal limits",
            'ratio' => $ratio,
            'current' => $current,
            'maximum' => $maximum
        ];
    }

    /**
     * Check boolean health condition
     */
    public function checkBooleanHealth(
        string $component,
        string $check,
        bool $condition,
        string $healthyMessage,
        string $unhealthyMessage
    ): array {
        if ($condition) {
            return [
                'status' => 'healthy',
                'message' => $healthyMessage,
                'check' => $check
            ];
        }
        
        $this->logger->warning("{$component} {$check} failed", [
            'component' => $component,
            'check' => $check,
            'message' => $unhealthyMessage
        ]);
        
        return [
            'status' => 'unhealthy',
            'message' => $unhealthyMessage,
            'check' => $check
        ];
    }

    /**
     * Aggregate multiple health checks into overall status
     */
    public function aggregateHealthChecks(array $checks): array
    {
        $statuses = array_column($checks, 'status');
        $messages = array_column($checks, 'message');
        
        // Determine overall status priority: critical > unhealthy > warning > healthy
        $overallStatus = $this->determineOverallStatus($statuses);
        
        return [
            'overall_status' => $overallStatus,
            'summary' => $this->generateHealthSummary($overallStatus, $checks),
            'checks' => $checks,
            'total_checks' => count($checks),
            'healthy_checks' => count(array_filter($statuses, fn($s) => $s === 'healthy')),
            'warning_checks' => count(array_filter($statuses, fn($s) => $s === 'warning')),
            'unhealthy_checks' => count(array_filter($statuses, fn($s) => $s === 'unhealthy')),
            'critical_checks' => count(array_filter($statuses, fn($s) => $s === 'critical'))
        ];
    }

    /**
     * Check connectivity health
     */
    public function checkConnectivity(string $component, callable $connectivityTest): array
    {
        try {
            $result = $connectivityTest();
            
            if ($result === true || (is_array($result) && ($result['connected'] ?? false))) {
                return [
                    'status' => 'healthy',
                    'message' => 'Connectivity successful',
                    'component' => $component
                ];
            }
            
            $this->logger->warning("{$component} connectivity failed", [
                'component' => $component,
                'result' => $result
            ]);
            
            return [
                'status' => 'unhealthy',
                'message' => 'Connectivity failed',
                'component' => $component,
                'details' => $result
            ];
            
        } catch (\Throwable $e) {
            $this->logger->error("{$component} connectivity error", [
                'component' => $component,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'critical',
                'message' => 'Connectivity error: ' . $e->getMessage(),
                'component' => $component,
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Check performance health based on metrics
     */
    public function checkPerformanceHealth(
        string $component,
        array $metrics,
        array $thresholds = []
    ): array {
        $checks = [];
        
        foreach ($metrics as $metric => $value) {
            $threshold = $thresholds[$metric] ?? null;
            
            if ($threshold === null) {
                continue;
            }
            
            if (is_array($threshold)) {
                // Range-based threshold
                $min = $threshold['min'] ?? null;
                $max = $threshold['max'] ?? null;
                
                if ($min !== null && $value < $min) {
                    $checks[] = [
                        'status' => 'warning',
                        'message' => "{$metric} below minimum threshold",
                        'metric' => $metric,
                        'value' => $value,
                        'threshold' => $min
                    ];
                } elseif ($max !== null && $value > $max) {
                    $checks[] = [
                        'status' => 'warning',
                        'message' => "{$metric} above maximum threshold",
                        'metric' => $metric,
                        'value' => $value,
                        'threshold' => $max
                    ];
                } else {
                    $checks[] = [
                        'status' => 'healthy',
                        'message' => "{$metric} within acceptable range",
                        'metric' => $metric,
                        'value' => $value
                    ];
                }
            } else {
                // Simple threshold
                if ($value < $threshold) {
                    $checks[] = [
                        'status' => 'warning',
                        'message' => "{$metric} below threshold",
                        'metric' => $metric,
                        'value' => $value,
                        'threshold' => $threshold
                    ];
                } else {
                    $checks[] = [
                        'status' => 'healthy',
                        'message' => "{$metric} above threshold",
                        'metric' => $metric,
                        'value' => $value
                    ];
                }
            }
        }
        
        return $this->aggregateHealthChecks($checks);
    }

    /**
     * Create a standardized health check result
     */
    public function createHealthResult(
        string $status,
        string $message,
        array $context = []
    ): array {
        return array_merge([
            'status' => $status,
            'message' => $message,
            'timestamp' => PerformanceTimer::now(),
            'component' => $context['component'] ?? 'unknown'
        ], $context);
    }

    /**
     * Determine overall status from multiple statuses
     */
    private function determineOverallStatus(array $statuses): string
    {
        if (in_array('critical', $statuses)) {
            return 'critical';
        }
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Generate health summary message
     */
    private function generateHealthSummary(string $overallStatus, array $checks): string
    {
        $totalChecks = count($checks);
        $statusCounts = array_count_values(array_column($checks, 'status'));
        
        switch ($overallStatus) {
            case 'critical':
                $criticalCount = $statusCounts['critical'] ?? 0;
                return "System critical: {$criticalCount}/{$totalChecks} checks failed critically";
                
            case 'unhealthy':
                $unhealthyCount = $statusCounts['unhealthy'] ?? 0;
                return "System unhealthy: {$unhealthyCount}/{$totalChecks} checks failed";
                
            case 'warning':
                $warningCount = $statusCounts['warning'] ?? 0;
                return "System warning: {$warningCount}/{$totalChecks} checks show warnings";
                
            default:
                return "System healthy: All {$totalChecks} checks passed";
        }
    }
}
