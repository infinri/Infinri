<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Temporal;

/**
 * Temporal Engine - Time-Aware Consciousness Logic
 * 
 * Provides temporal reasoning capabilities including business hours detection,
 * staleness checks, time window validation, and temporal safety evaluation.
 * 
 * @architecture Temporal consciousness and time-aware logic
 * @author Infinri Framework
 * @version 1.0.0
 */
final class TemporalEngine
{
    private array $config;
    private \DateTimeZone $timezone;

    /**
     * Initialize temporal engine
     * 
     * @param array $config Temporal configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'default_timezone' => 'UTC',
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'weekend_execution' => false,
            'holiday_calendar' => 'US',
            'staleness_threshold' => 3600
        ], $config);
        
        $this->timezone = new \DateTimeZone($this->config['default_timezone']);
    }

    /**
     * Check if current time is within business hours
     * 
     * @return bool True if within business hours
     */
    public function isBusinessHours(): bool
    {
        $now = new \DateTime('now', $this->timezone);
        $dayOfWeek = (int)$now->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Check weekend execution policy
        if (!$this->config['weekend_execution'] && ($dayOfWeek === 0 || $dayOfWeek === 6)) {
            return false;
        }
        
        $currentTime = $now->format('H:i');
        $startTime = $this->config['business_hours_start'];
        $endTime = $this->config['business_hours_end'];
        
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Check if data is stale based on timestamp
     * 
     * @param float $timestamp Unix timestamp to check
     * @return bool True if data is stale
     */
    public function isStale(float $timestamp): bool
    {
        $currentTime = microtime(true);
        $age = $currentTime - $timestamp;
        
        return $age > $this->config['staleness_threshold'];
    }

    /**
     * Check if current time is within specified time window
     * 
     * @param string $startTime Start time (H:i format)
     * @param string $endTime End time (H:i format)
     * @return bool True if within time window
     */
    public function isWithinTimeWindow(string $startTime, string $endTime): bool
    {
        $now = new \DateTime('now', $this->timezone);
        $currentTime = $now->format('H:i');
        
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Evaluate temporal safety for execution
     * 
     * @param array $context Execution context
     * @return bool True if temporally safe to execute
     */
    public function isTemporallySafe(array $context = []): bool
    {
        // Check business hours if required
        if (isset($context['requires_business_hours']) && $context['requires_business_hours']) {
            if (!$this->isBusinessHours()) {
                return false;
            }
        }
        
        // Check specific time window if provided
        if (isset($context['time_window'])) {
            $window = $context['time_window'];
            if (isset($window['start']) && isset($window['end'])) {
                if (!$this->isWithinTimeWindow($window['start'], $window['end'])) {
                    return false;
                }
            }
        }
        
        // Check data staleness if timestamp provided
        if (isset($context['data_timestamp'])) {
            if ($this->isStale($context['data_timestamp'])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get current temporal context
     * 
     * @return array Current temporal context
     */
    public function getCurrentContext(): array
    {
        $now = new \DateTime('now', $this->timezone);
        
        return [
            'timestamp' => microtime(true),
            'datetime' => $now->format('Y-m-d H:i:s'),
            'timezone' => $this->config['default_timezone'],
            'is_business_hours' => $this->isBusinessHours(),
            'day_of_week' => (int)$now->format('w'),
            'hour' => (int)$now->format('H'),
            'minute' => (int)$now->format('i')
        ];
    }

    /**
     * Calculate time until next business hours
     * 
     * @return int Seconds until next business hours
     */
    public function getSecondsUntilBusinessHours(): int
    {
        if ($this->isBusinessHours()) {
            return 0;
        }
        
        $now = new \DateTime('now', $this->timezone);
        $nextBusinessDay = clone $now;
        
        // Find next business day
        while (!$this->isBusinessDay($nextBusinessDay)) {
            $nextBusinessDay->add(new \DateInterval('P1D'));
        }
        
        // Set to business hours start
        $businessStart = \DateTime::createFromFormat(
            'Y-m-d H:i',
            $nextBusinessDay->format('Y-m-d') . ' ' . $this->config['business_hours_start'],
            $this->timezone
        );
        
        return max(0, $businessStart->getTimestamp() - $now->getTimestamp());
    }

    /**
     * Check if given date is a business day
     * 
     * @param \DateTime $date Date to check
     * @return bool True if business day
     */
    private function isBusinessDay(\DateTime $date): bool
    {
        $dayOfWeek = (int)$date->format('w');
        
        // Weekend check
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return $this->config['weekend_execution'];
        }
        
        // Could add holiday checking here based on holiday_calendar config
        
        return true;
    }

    /**
     * Format duration in human-readable format
     * 
     * @param int $seconds Duration in seconds
     * @return string Human-readable duration
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes} minutes";
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return "{$hours} hours";
        } else {
            $days = floor($seconds / 86400);
            return "{$days} days";
        }
    }
}
