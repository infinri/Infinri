<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Common;

use Infinri\SwarmFramework\Core\Attributes\Injectable;

/**
 * Performance Timer - Centralized timing utilities
 * 
 * Eliminates redundant microtime(true) patterns across the codebase
 * and provides consistent performance measurement and logging.
 */
#[Injectable(dependencies: [])]
final class PerformanceTimer
{
    private static array $activeTimers = [];
    private static array $completedTimers = [];

    /**
     * Start a named timer
     */
    public static function start(string $name): float
    {
        $startTime = microtime(true);
        self::$activeTimers[$name] = $startTime;
        return $startTime;
    }

    /**
     * Stop a named timer and return duration
     */
    public static function stop(string $name): float
    {
        $endTime = microtime(true);
        
        if (!isset(self::$activeTimers[$name])) {
            throw new \InvalidArgumentException("Timer '{$name}' was not started");
        }
        
        $startTime = self::$activeTimers[$name];
        $duration = $endTime - $startTime;
        
        // Move to completed timers
        self::$completedTimers[$name] = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'duration_ms' => round($duration * 1000, 2)
        ];
        
        unset(self::$activeTimers[$name]);
        
        return $duration;
    }

    /**
     * Get elapsed time for an active timer without stopping it
     */
    public static function elapsed(string $name): float
    {
        if (!isset(self::$activeTimers[$name])) {
            throw new \InvalidArgumentException("Timer '{$name}' is not active");
        }
        
        return microtime(true) - self::$activeTimers[$name];
    }

    /**
     * Get elapsed time in milliseconds
     */
    public static function elapsedMs(string $name): float
    {
        return round(self::elapsed($name) * 1000, 2);
    }

    /**
     * Execute a callable and measure its duration
     */
    public static function measure(string $name, callable $callable): mixed
    {
        self::start($name);
        
        try {
            $result = $callable();
            self::stop($name);
            return $result;
        } catch (\Throwable $e) {
            self::stop($name);
            throw $e;
        }
    }

    /**
     * Execute a callable and return both result and duration
     */
    public static function measureWithDuration(string $name, callable $callable): array
    {
        self::start($name);
        
        try {
            $result = $callable();
            $duration = self::stop($name);
            
            return [
                'result' => $result,
                'duration' => $duration,
                'duration_ms' => round($duration * 1000, 2)
            ];
        } catch (\Throwable $e) {
            $duration = self::stop($name);
            throw $e;
        }
    }

    /**
     * Get timer statistics
     */
    public static function getTimerStats(string $name): ?array
    {
        return self::$completedTimers[$name] ?? null;
    }

    /**
     * Get all completed timer statistics
     */
    public static function getAllStats(): array
    {
        return self::$completedTimers;
    }

    /**
     * Get active timers
     */
    public static function getActiveTimers(): array
    {
        $active = [];
        $currentTime = microtime(true);
        
        foreach (self::$activeTimers as $name => $startTime) {
            $active[$name] = [
                'start_time' => $startTime,
                'elapsed' => $currentTime - $startTime,
                'elapsed_ms' => round(($currentTime - $startTime) * 1000, 2)
            ];
        }
        
        return $active;
    }

    /**
     * Clear completed timer history
     */
    public static function clearHistory(): void
    {
        self::$completedTimers = [];
    }

    /**
     * Clear all timers (active and completed)
     */
    public static function clearAll(): void
    {
        self::$activeTimers = [];
        self::$completedTimers = [];
    }

    /**
     * Check if a timer is active
     */
    public static function isActive(string $name): bool
    {
        return isset(self::$activeTimers[$name]);
    }

    /**
     * Get performance summary for analysis
     */
    public static function getPerformanceSummary(): array
    {
        $summary = [
            'active_timers' => count(self::$activeTimers),
            'completed_timers' => count(self::$completedTimers),
            'total_operations' => count(self::$completedTimers),
            'average_duration_ms' => 0,
            'slowest_operation' => null,
            'fastest_operation' => null
        ];

        if (!empty(self::$completedTimers)) {
            $durations = array_column(self::$completedTimers, 'duration_ms');
            $summary['average_duration_ms'] = round(array_sum($durations) / count($durations), 2);
            
            $maxDuration = max($durations);
            $minDuration = min($durations);
            
            foreach (self::$completedTimers as $name => $stats) {
                if ($stats['duration_ms'] === $maxDuration) {
                    $summary['slowest_operation'] = [
                        'name' => $name,
                        'duration_ms' => $maxDuration
                    ];
                }
                
                if ($stats['duration_ms'] === $minDuration) {
                    $summary['fastest_operation'] = [
                        'name' => $name,
                        'duration_ms' => $minDuration
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * Format duration for human-readable output
     */
    public static function formatDuration(float $duration, int $precision = 2): string
    {
        if ($duration < 0.001) {
            return round($duration * 1000000, 2) . 'μs';
        } elseif ($duration < 1) {
            return round($duration * 1000, 2) . 'ms';
        } else {
            return round($duration, $precision) . 's';
        }
    }

    /**
     * Get current timestamp for consistent timing
     */
    public static function now(): float
    {
        return microtime(true);
    }

    /**
     * Calculate duration between two timestamps
     */
    public static function duration(float $startTime, ?float $endTime = null): float
    {
        return ($endTime ?? self::now()) - $startTime;
    }

    /**
     * Format duration in milliseconds with precision
     */
    public static function formatDurationMs(float $duration, int $precision = 2): float
    {
        return round($duration * 1000, $precision);
    }

    /**
     * Calculate duration in milliseconds
     */
    public static function durationMs(float $startTime, ?float $endTime = null): float
    {
        return self::formatDurationMs(self::duration($startTime, $endTime));
    }

    /**
     * Generate unique timestamp-based ID
     */
    public static function generateUniqueId(string $prefix = ''): string
    {
        $timestamp = self::now();
        $randomBytes = bin2hex(random_bytes(4));
        return empty($prefix) ? "{$timestamp}_{$randomBytes}" : "{$prefix}_{$timestamp}_{$randomBytes}";
    }

    /**
     * Generate hash-based unique ID with timestamp
     */
    public static function generateHashId(string $input): string
    {
        return hash('sha256', $input . self::now() . random_bytes(8));
    }

    /**
     * Create performance context array for logging
     */
    public static function createContext(string $operation, float $startTime, array $additionalContext = []): array
    {
        $duration = self::duration($startTime);
        
        return array_merge([
            'operation' => $operation,
            'duration_ms' => self::formatDurationMs($duration),
            'timestamp' => self::now()
        ], $additionalContext);
    }



    /**
     * Start timing with automatic context creation
     */
    public static function startWithContext(string $name, array $context = []): array
    {
        $startTime = self::start($name);
        
        return array_merge([
            'operation' => $name,
            'start_time' => $startTime,
            'timestamp' => $startTime
        ], $context);
    }

    /**
     * Stop timing and return formatted context
     */
    public static function stopWithContext(string $name, array $startContext = []): array
    {
        $duration = self::stop($name);
        
        return array_merge($startContext, [
            'duration' => $duration,
            'duration_ms' => self::formatDurationMs($duration),
            'end_timestamp' => self::now()
        ]);
    }
}
