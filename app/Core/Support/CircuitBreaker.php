<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Support;

use App\Core\Contracts\Cache\CacheInterface;
use Throwable;

/**
 * Circuit Breaker
 * 
 * Implements the circuit breaker pattern for external service calls.
 * Prevents cascading failures by failing fast when a service is unhealthy.
 * 
 * States:
 * - CLOSED: Normal operation, requests go through
 * - OPEN: Service is failing, requests fail immediately
 * - HALF_OPEN: Testing if service has recovered
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    /**
     * Cache for storing circuit state
     */
    protected CacheInterface $cache;

    /**
     * Circuit breaker options per service
     */
    protected array $options = [];

    /**
     * Default options
     */
    protected array $defaults = [
        'failure_threshold' => 5,      // Failures before opening
        'success_threshold' => 2,      // Successes to close from half-open
        'timeout' => 60,               // Seconds before trying half-open
        'sample_window' => 120,        // Window for counting failures
    ];

    public function __construct(CacheInterface $cache, array $options = [])
    {
        $this->cache = $cache;
        $this->options = $options;
    }

    /**
     * Execute a call through the circuit breaker
     * 
     * @param string $service Service identifier
     * @param callable $operation The operation to execute
     * @param callable|null $fallback Fallback if circuit is open or operation fails
     * @return mixed
     * @throws CircuitBreakerOpenException If circuit is open and no fallback
     */
    public function call(string $service, callable $operation, ?callable $fallback = null): mixed
    {
        $state = $this->getState($service);

        // If circuit is open, fail fast
        if ($state === self::STATE_OPEN) {
            // Check if we should try half-open
            if ($this->shouldAttemptReset($service)) {
                $this->setState($service, self::STATE_HALF_OPEN);
            } else {
                return $this->handleFailure($service, null, $fallback, 'Circuit is open');
            }
        }

        try {
            $result = $operation();

            // Record success
            $this->recordSuccess($service);

            return $result;

        } catch (Throwable $e) {
            return $this->handleFailure($service, $e, $fallback, $e->getMessage());
        }
    }

    /**
     * Handle operation failure
     */
    protected function handleFailure(
        string $service, 
        ?Throwable $exception, 
        ?callable $fallback,
        string $reason
    ): mixed {
        // Record failure
        if ($exception !== null) {
            $this->recordFailure($service);
        }

        // Try fallback
        if ($fallback !== null) {
            try {
                return $fallback($exception);
            } catch (Throwable) {
                // Fallback also failed, throw original or circuit exception
            }
        }

        if ($exception !== null) {
            throw $exception;
        }

        throw new CircuitBreakerOpenException("Circuit breaker open for {$service}: {$reason}");
    }

    /**
     * Record a successful operation
     */
    protected function recordSuccess(string $service): void
    {
        $state = $this->getState($service);
        $options = $this->getOptions($service);

        if ($state === self::STATE_HALF_OPEN) {
            // Increment success count in half-open state
            $successes = $this->incrementCounter($service, 'half_open_successes');

            if ($successes >= $options['success_threshold']) {
                // Enough successes, close the circuit
                $this->setState($service, self::STATE_CLOSED);
                $this->resetCounters($service);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success in closed state
            $this->cache->forget($this->cacheKey($service, 'failures'));
        }
    }

    /**
     * Record a failed operation
     */
    protected function recordFailure(string $service): void
    {
        $state = $this->getState($service);
        $options = $this->getOptions($service);

        if ($state === self::STATE_HALF_OPEN) {
            // Any failure in half-open goes back to open
            $this->setState($service, self::STATE_OPEN);
            $this->setOpenTime($service);
            $this->resetCounters($service);

        } elseif ($state === self::STATE_CLOSED) {
            // Increment failure count
            $failures = $this->incrementCounter($service, 'failures', $options['sample_window']);

            if ($failures >= $options['failure_threshold']) {
                // Too many failures, open the circuit
                $this->setState($service, self::STATE_OPEN);
                $this->setOpenTime($service);
                $this->resetCounters($service);
            }
        }
    }

    /**
     * Get the current state of a circuit
     */
    public function getState(string $service): string
    {
        return $this->cache->get(
            $this->cacheKey($service, 'state'),
            self::STATE_CLOSED
        );
    }

    /**
     * Set the state of a circuit
     */
    protected function setState(string $service, string $state): void
    {
        $this->cache->put($this->cacheKey($service, 'state'), $state);
    }

    /**
     * Check if we should attempt to reset (transition to half-open)
     */
    protected function shouldAttemptReset(string $service): bool
    {
        $openTime = $this->cache->get($this->cacheKey($service, 'open_time'));
        $options = $this->getOptions($service);

        if ($openTime === null) {
            return true;
        }

        return (time() - $openTime) >= $options['timeout'];
    }

    /**
     * Set the time when circuit was opened
     */
    protected function setOpenTime(string $service): void
    {
        $this->cache->put($this->cacheKey($service, 'open_time'), time());
    }

    /**
     * Increment a counter
     */
    protected function incrementCounter(string $service, string $counter, ?int $ttl = null): int
    {
        $key = $this->cacheKey($service, $counter);
        $value = (int) $this->cache->get($key, 0);
        $value++;

        $this->cache->put($key, $value, $ttl ?? 3600);

        return $value;
    }

    /**
     * Reset all counters for a service
     */
    protected function resetCounters(string $service): void
    {
        $this->cache->forget($this->cacheKey($service, 'failures'));
        $this->cache->forget($this->cacheKey($service, 'half_open_successes'));
    }

    /**
     * Get options for a service
     */
    protected function getOptions(string $service): array
    {
        return array_merge(
            $this->defaults,
            $this->options[$service] ?? [],
        );
    }

    /**
     * Generate cache key
     */
    protected function cacheKey(string $service, string $key): string
    {
        return "circuit_breaker:{$service}:{$key}";
    }

    /**
     * Manually force a circuit to open
     */
    public function forceOpen(string $service): void
    {
        $this->setState($service, self::STATE_OPEN);
        $this->setOpenTime($service);
    }

    /**
     * Manually force a circuit to close
     */
    public function forceClose(string $service): void
    {
        $this->setState($service, self::STATE_CLOSED);
        $this->resetCounters($service);
        $this->cache->forget($this->cacheKey($service, 'open_time'));
    }

    /**
     * Get circuit breaker statistics for a service
     */
    public function getStats(string $service): array
    {
        return [
            'state' => $this->getState($service),
            'failures' => (int) $this->cache->get($this->cacheKey($service, 'failures'), 0),
            'half_open_successes' => (int) $this->cache->get($this->cacheKey($service, 'half_open_successes'), 0),
            'open_time' => $this->cache->get($this->cacheKey($service, 'open_time')),
            'options' => $this->getOptions($service),
        ];
    }

    /**
     * Check if a service is available (circuit is closed or half-open)
     */
    public function isAvailable(string $service): bool
    {
        $state = $this->getState($service);

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN && $this->shouldAttemptReset($service)) {
            return true; // Will transition to half-open
        }

        return $state === self::STATE_HALF_OPEN;
    }
}
