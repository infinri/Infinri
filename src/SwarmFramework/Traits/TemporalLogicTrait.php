<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Traits;

use Infinri\SwarmFramework\Core\Temporal\TemporalEngine;

/**
 * TemporalLogicTrait - Temporal Consciousness Logic
 * 
 * Provides consciousness-level temporal reasoning capabilities to SwarmUnits.
 * Implements time-aware decision making and temporal state management.
 * 
 * @architecture Reusable temporal consciousness behaviors
 * @reference swarm_framework_pattern_blueprint.md → Temporal Logic Pattern
 * @author Infinri Framework
 * @version 1.0.0
 */
trait TemporalLogicTrait
{
    protected TemporalEngine $temporal;

    /**
     * Check if a value is stale based on timestamp
     * 
     * @param mixed $value Value to check (must have timestamp)
     * @param int $maxAgeSeconds Maximum age in seconds
     * @return bool True if value is stale
     */
    protected function stale(mixed $value, int $maxAgeSeconds): bool
    {
        if (!isset($value['timestamp'])) {
            return true; // No timestamp = consider stale
        }
        
        return (time() - $value['timestamp']) > $maxAgeSeconds;
    }

    /**
     * Check if current time is during business hours
     * 
     * @param string $timezone Timezone to check (default: system timezone)
     * @return bool True if during business hours
     */
    protected function duringBusinessHours(string $timezone = 'UTC'): bool
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);
        
        $hour = (int)date('H');
        $dayOfWeek = (int)date('N'); // 1 = Monday, 7 = Sunday
        
        date_default_timezone_set($originalTimezone);
        
        return $dayOfWeek <= 5 && $hour >= 9 && $hour <= 17;
    }

    /**
     * Check if current time is within a specific time window
     * 
     * @param string $startTime Start time (HH:MM format)
     * @param string $endTime End time (HH:MM format)
     * @param string $timezone Timezone to check
     * @return bool True if within time window
     */
    protected function withinTimeWindow(string $startTime, string $endTime, string $timezone = 'UTC'): bool
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);
        
        $currentTime = date('H:i');
        $result = $currentTime >= $startTime && $currentTime <= $endTime;
        
        date_default_timezone_set($originalTimezone);
        
        return $result;
    }

    /**
     * Get age of a timestamped value in seconds
     * 
     * @param mixed $value Value with timestamp
     * @return int Age in seconds (0 if no timestamp)
     */
    protected function getAge(mixed $value): int
    {
        if (!isset($value['timestamp'])) {
            return 0;
        }
        
        return time() - $value['timestamp'];
    }

    /**
     * Check if value is fresh (not stale)
     * 
     * @param mixed $value Value to check
     * @param int $maxAgeSeconds Maximum age in seconds
     * @return bool True if value is fresh
     */
    protected function fresh(mixed $value, int $maxAgeSeconds): bool
    {
        return !$this->stale($value, $maxAgeSeconds);
    }

    /**
     * Add timestamp to data for temporal tracking
     * 
     * @param array $data Data to timestamp
     * @return array Data with timestamp added
     */
    protected function addTimestamp(array $data): array
    {
        $data['timestamp'] = microtime(true);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    /**
     * Check if it's safe to execute based on temporal constraints
     * 
     * @param array $constraints Temporal constraints
     * @return bool True if safe to execute
     */
    protected function temporallySafeToExecute(array $constraints = []): bool
    {
        // Check business hours constraint
        if (isset($constraints['business_hours_only']) && $constraints['business_hours_only']) {
            if (!$this->duringBusinessHours($constraints['timezone'] ?? 'UTC')) {
                return false;
            }
        }

        // Check time window constraint
        if (isset($constraints['time_window'])) {
            $window = $constraints['time_window'];
            if (!$this->withinTimeWindow(
                $window['start'], 
                $window['end'], 
                $constraints['timezone'] ?? 'UTC'
            )) {
                return false;
            }
        }

        // Check cooldown constraint
        if (isset($constraints['last_execution'])) {
            $cooldown = $constraints['cooldown_seconds'] ?? 300; // 5 minutes default
            if ($this->getAge($constraints['last_execution']) < $cooldown) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get temporal context for consciousness monitoring
     * 
     * @return array Temporal context
     */
    protected function getTemporalContext(): array
    {
        return [
            'current_timestamp' => microtime(true),
            'current_datetime' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'is_business_hours' => $this->duringBusinessHours(),
            'day_of_week' => (int)date('N'),
            'hour_of_day' => (int)date('H'),
            'unit_id' => $this->getIdentity()->id
        ];
    }

    /**
     * Calculate time until next business hours
     * 
     * @param string $timezone Timezone to calculate for
     * @return int Seconds until next business hours
     */
    protected function timeUntilBusinessHours(string $timezone = 'UTC'): int
    {
        if ($this->duringBusinessHours($timezone)) {
            return 0; // Already in business hours
        }

        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);
        
        $currentHour = (int)date('H');
        $currentDay = (int)date('N');
        
        // If it's weekend, wait until Monday 9 AM
        if ($currentDay > 5) {
            $daysUntilMonday = 8 - $currentDay;
            $hoursUntilMonday = $daysUntilMonday * 24 - $currentHour + 9;
            $result = $hoursUntilMonday * 3600;
        } else {
            // If before 9 AM, wait until 9 AM today
            if ($currentHour < 9) {
                $result = (9 - $currentHour) * 3600;
            } else {
                // After 5 PM, wait until 9 AM tomorrow
                $hoursUntilTomorrow = 24 - $currentHour + 9;
                $result = $hoursUntilTomorrow * 3600;
            }
        }
        
        date_default_timezone_set($originalTimezone);
        
        return $result;
    }

    /**
     * Check if execution should be delayed based on temporal rules
     * 
     * @param array $temporalRules Temporal execution rules
     * @return array Delay information [should_delay => bool, delay_seconds => int, reason => string]
     */
    protected function shouldDelayExecution(array $temporalRules = []): array
    {
        $result = [
            'should_delay' => false,
            'delay_seconds' => 0,
            'reason' => ''
        ];

        // Check if business hours required
        if (isset($temporalRules['require_business_hours']) && $temporalRules['require_business_hours']) {
            if (!$this->duringBusinessHours($temporalRules['timezone'] ?? 'UTC')) {
                $result['should_delay'] = true;
                $result['delay_seconds'] = $this->timeUntilBusinessHours($temporalRules['timezone'] ?? 'UTC');
                $result['reason'] = 'outside_business_hours';
                return $result;
            }
        }

        // Check minimum interval between executions
        if (isset($temporalRules['min_interval_seconds']) && isset($temporalRules['last_execution'])) {
            $timeSinceLastExecution = $this->getAge($temporalRules['last_execution']);
            $minInterval = $temporalRules['min_interval_seconds'];
            
            if ($timeSinceLastExecution < $minInterval) {
                $result['should_delay'] = true;
                $result['delay_seconds'] = $minInterval - $timeSinceLastExecution;
                $result['reason'] = 'min_interval_not_met';
                return $result;
            }
        }

        return $result;
    }
}
