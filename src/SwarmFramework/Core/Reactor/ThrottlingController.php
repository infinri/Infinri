<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Reactor;

use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Throttling Controller - Adaptive Performance Management
 * 
 * Manages adaptive throttling based on system performance metrics
 * to maintain optimal execution rates and prevent system overload.
 * 
 * @author Infinri Framework
 * @version 1.0.0
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class ThrottlingController
{
    use LoggerTrait;
    
    private array $throttleConfig;
    private array $config;
    private float $currentThrottleRate = 1.0;
    private array $performanceHistory = [];
    private int $adjustmentCounter = 0;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('ThrottlingController', $config);
    }

    /**
     * Adjust throttling based on performance metrics
     */
    public function adjustThrottling(float $tickStart, array $healthMetrics): void
    {
        if (!$this->config['enable_adaptive_throttling']) {
            return;
        }

        $tickDuration = (PerformanceTimer::now() - $tickStart) * 1000; // Convert to ms
        $targetDuration = $this->config['target_tick_duration_ms'];

        // Record performance data
        $this->recordPerformanceData($tickDuration, $healthMetrics);

        // Calculate throttling adjustment
        $adjustment = $this->calculateThrottlingAdjustment($tickDuration, $targetDuration);

        if ($adjustment !== 0) {
            $oldRate = $this->currentThrottleRate;
            $this->currentThrottleRate = max(
                $this->config['max_throttle_rate'],
                min(1.0, $this->currentThrottleRate + $adjustment)
            );

            $this->adjustmentCounter++;

            $this->logger->info('Throttling adjusted', $this->buildOperationContext('throttle_adjustment', [
                'tick_duration_ms' => $tickDuration,
                'target_duration_ms' => $targetDuration,
                'old_throttle_rate' => $oldRate,
                'new_throttle_rate' => $this->currentThrottleRate,
                'adjustment' => $adjustment,
                'adjustment_count' => $this->adjustmentCounter
            ]));

            // Apply throttling delay if needed
            if ($this->currentThrottleRate < 1.0) {
                $this->applyThrottlingDelay();
            }
        }
    }

    /**
     * Calculate throttling adjustment based on performance
     */
    private function calculateThrottlingAdjustment(float $actualDuration, float $targetDuration): float
    {
        $performanceRatio = $actualDuration / $targetDuration;
        $adjustmentFactor = $this->config['throttle_adjustment_factor'];

        if ($performanceRatio > 1.2) {
            // System is running slow - increase throttling (decrease rate)
            return -$adjustmentFactor * ($performanceRatio - 1.0);
        } elseif ($performanceRatio < 0.8 && $this->currentThrottleRate < 1.0) {
            // System is running fast and we're throttled - decrease throttling (increase rate)
            return $adjustmentFactor * (1.0 - $performanceRatio);
        }

        return 0; // No adjustment needed
    }

    /**
     * Record performance data for trend analysis
     */
    private function recordPerformanceData(float $tickDuration, array $healthMetrics): void
    {
        $dataPoint = [
            'timestamp' => PerformanceTimer::now(),
            'tick_duration_ms' => $tickDuration,
            'throttle_rate' => $this->currentThrottleRate,
            'success_rate' => $healthMetrics['overall_success_rate'] ?? 1.0,
            'avg_execution_duration' => $healthMetrics['avg_execution_duration'] ?? 0.0,
            'memory_usage' => $healthMetrics['avg_memory_usage'] ?? 0
        ];

        $this->performanceHistory[] = $dataPoint;

        // Maintain window size
        $windowSize = $this->config['performance_window_size'];
        if (count($this->performanceHistory) > $windowSize) {
            array_shift($this->performanceHistory);
        }
    }

    /**
     * Apply throttling delay
     */
    private function applyThrottlingDelay(): void
    {
        // Calculate delay based on throttle rate
        $baseDelay = 1000; // 1ms base delay
        $delay = $baseDelay * (1.0 - $this->currentThrottleRate);
        
        if ($delay > 0) {
            usleep((int)$delay); // Microseconds
        }
    }

    /**
     * Get current throttle rate
     */
    public function getCurrentThrottleRate(): float
    {
        return $this->currentThrottleRate;
    }

    /**
     * Get throttling statistics
     */
    public function getThrottlingStats(): array
    {
        $recentPerformance = $this->getRecentPerformanceStats();
        
        return [
            'current_throttle_rate' => $this->currentThrottleRate,
            'adjustment_count' => $this->adjustmentCounter,
            'target_tick_duration_ms' => $this->config['target_tick_duration_ms'],
            'adaptive_throttling_enabled' => $this->config['enable_adaptive_throttling'],
            'recent_performance' => $recentPerformance,
            'performance_trend' => $this->calculatePerformanceTrend()
        ];
    }

    /**
     * Get recent performance statistics
     */
    private function getRecentPerformanceStats(): array
    {
        if (empty($this->performanceHistory)) {
            return [
                'avg_tick_duration_ms' => 0,
                'avg_success_rate' => 1.0,
                'avg_throttle_rate' => 1.0
            ];
        }

        $tickDurations = array_column($this->performanceHistory, 'tick_duration_ms');
        $successRates = array_column($this->performanceHistory, 'success_rate');
        $throttleRates = array_column($this->performanceHistory, 'throttle_rate');

        return [
            'avg_tick_duration_ms' => array_sum($tickDurations) / count($tickDurations),
            'min_tick_duration_ms' => min($tickDurations),
            'max_tick_duration_ms' => max($tickDurations),
            'avg_success_rate' => array_sum($successRates) / count($successRates),
            'avg_throttle_rate' => array_sum($throttleRates) / count($throttleRates),
            'data_points' => count($this->performanceHistory)
        ];
    }

    /**
     * Calculate performance trend
     */
    private function calculatePerformanceTrend(): string
    {
        if (count($this->performanceHistory) < 3) {
            return 'insufficient_data';
        }

        $recent = array_slice($this->performanceHistory, -3);
        $durations = array_column($recent, 'tick_duration_ms');
        
        $trend = 0;
        for ($i = 1; $i < count($durations); $i++) {
            if ($durations[$i] > $durations[$i - 1]) {
                $trend++;
            } elseif ($durations[$i] < $durations[$i - 1]) {
                $trend--;
            }
        }

        if ($trend > 0) {
            return 'degrading';
        } elseif ($trend < 0) {
            return 'improving';
        } else {
            return 'stable';
        }
    }

    /**
     * Reset throttling to default state
     */
    public function resetThrottling(): void
    {
        $this->currentThrottleRate = 1.0;
        $this->performanceHistory = [];
        $this->adjustmentCounter = 0;
        
        $this->logger->info('Throttling reset to default state', $this->buildOperationContext('throttle_reset', [
            'reason' => 'manual_reset'
        ]));
    }

    /**
     * Force throttle rate (for testing or emergency situations)
     */
    public function forceThrottleRate(float $rate): void
    {
        $rate = max($this->config['max_throttle_rate'], min(1.0, $rate));
        $oldRate = $this->currentThrottleRate;
        $this->currentThrottleRate = $rate;
        
        $this->logger->warning('Throttle rate forced', $this->buildOperationContext('throttle_forced', [
            'old_rate' => $oldRate,
            'new_rate' => $rate,
            'reason' => 'forced_override'
        ]));
    }

    /**
     * Check if system is being throttled
     */
    public function isThrottled(): bool
    {
        return $this->currentThrottleRate < 1.0;
    }

    /**
     * Get throttling severity level
     */
    public function getThrottlingSeverity(): string
    {
        if ($this->currentThrottleRate >= 0.9) {
            return 'none';
        } elseif ($this->currentThrottleRate >= 0.7) {
            return 'light';
        } elseif ($this->currentThrottleRate >= 0.5) {
            return 'moderate';
        } elseif ($this->currentThrottleRate >= 0.3) {
            return 'heavy';
        } else {
            return 'severe';
        }
    }
}
