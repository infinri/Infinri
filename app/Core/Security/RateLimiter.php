<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Security;

use App\Core\Contracts\Cache\CacheInterface;

/**
 * Rate Limiter
 *
 * Limits the rate of actions by key (IP, user ID, etc).
 */
class RateLimiter
{
    /**
     * Cache store for rate limit data
     */
    protected CacheInterface $cache;

    /**
     * Prefix for cache keys
     */
    protected string $prefix = 'rate_limit:';

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check if the given key has too many attempts
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Increment the counter for the given key
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $cacheKey = $this->prefix . $key;

        $attempts = $this->cache->get($cacheKey, 0);
        $attempts++;

        $this->cache->put($cacheKey, $attempts, $decaySeconds);

        return $attempts;
    }

    /**
     * Get the number of attempts for the given key
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($this->prefix . $key, 0);
    }

    /**
     * Reset the number of attempts for the given key
     */
    public function resetAttempts(string $key): bool
    {
        return $this->cache->forget($this->prefix . $key);
    }

    /**
     * Get the number of retries left
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Clear rate limiter for a key
     */
    public function clear(string $key): bool
    {
        return $this->resetAttempts($key);
    }

    /**
     * Determine if the given key has been "accessed" too many times
     * and execute callback if not
     */
    public function attempt(
        string $key,
        int $maxAttempts,
        callable $callback,
        int $decaySeconds = 60
    ): mixed {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $result = $callback();

        $this->hit($key, $decaySeconds);

        return $result;
    }
}
