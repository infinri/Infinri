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
namespace App\Core\Cache;

use App\Core\Redis\Concerns\UsesRedis;
use App\Core\Redis\RedisManager;
use Redis;
use RedisException;

/**
 * Redis Cache Store
 *
 * High-performance cache store using Redis with support for
 * tags, atomic operations, and distributed caching.
 */
class RedisStore extends AbstractCacheStore
{
    use UsesRedis;

    public function __construct(
        protected RedisManager $redis,
        protected string $connection = 'cache',
        protected int $defaultTtl = 3600,
        protected string $prefix = 'cache:'
    ) {
    }

    /**
     * Get an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->redis()->get($this->key($key));

            if ($value === false) {
                return $default;
            }

            return $value;
        } catch (RedisException $e) {
            $this->logRedisError('Cache get', $e, ['key' => $key]);

            return $default;
        }
    }

    /**
     * Store an item in the cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= $this->defaultTtl;

        try {
            if ($ttl > 0) {
                return $this->redis()->setex($this->key($key), $ttl, $value);
            }

            return $this->redis()->set($this->key($key), $value);
        } catch (RedisException $e) {
            $this->logRedisError('Cache put', $e, ['key' => $key], 'error');

            return false;
        }
    }

    /**
     * Store an item if it doesn't exist
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= $this->defaultTtl;
        $prefixedKey = $this->key($key);

        try {
            // Use SETNX for atomic add
            if (! $this->redis()->setnx($prefixedKey, $value)) {
                return false;
            }

            // Set expiration if TTL provided
            if ($ttl > 0) {
                $this->redis()->expire($prefixedKey, $ttl);
            }

            return true;
        } catch (RedisException $e) {
            $this->logRedisError('Cache add', $e, ['key' => $key], 'error');

            return false;
        }
    }

    /**
     * Store an item forever
     */
    public function forever(string $key, mixed $value): bool
    {
        try {
            return $this->redis()->set($this->key($key), $value);
        } catch (RedisException $e) {
            $this->logRedisError('Cache forever', $e, ['key' => $key], 'error');

            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            return $this->redis()->del($this->key($key)) > 0;
        } catch (RedisException $e) {
            $this->logRedisError('Cache forget', $e, ['key' => $key]);

            return false;
        }
    }

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool
    {
        try {
            return $this->redis()->exists($this->key($key)) > 0;
        } catch (RedisException $e) {
            $this->logRedisError('Cache has check', $e, ['key' => $key]);

            return false;
        }
    }

    /**
     * Increment a value
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        try {
            return $this->redis()->incrBy($this->key($key), $value);
        } catch (RedisException $e) {
            $this->logRedisError('Cache increment', $e, ['key' => $key]);

            return false;
        }
    }

    /**
     * Decrement a value
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        try {
            return $this->redis()->decrBy($this->key($key), $value);
        } catch (RedisException $e) {
            $this->logRedisError('Cache decrement', $e, ['key' => $key]);

            return false;
        }
    }

    /**
     * Flush all items from the cache
     */
    public function flush(): bool
    {
        try {
            return $this->deleteByPattern($this->prefix . '*') !== false;
        } catch (RedisException $e) {
            $this->logRedisError('Cache flush', $e, [], 'error');

            return false;
        }
    }

    /**
     * Get multiple items
     */
    public function many(array $keys): array
    {
        $prefixedKeys = array_map(fn ($key) => $this->key($key), $keys);

        try {
            $values = $this->redis()->mget($prefixedKeys);

            $result = [];
            foreach ($keys as $i => $key) {
                $result[$key] = $values[$i] === false ? null : $values[$i];
            }

            return $result;
        } catch (RedisException $e) {
            $this->logRedisError('Cache many get', $e, ['keys' => $keys]);

            return array_fill_keys($keys, null);
        }
    }

    /**
     * Store multiple items
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        $ttl ??= $this->defaultTtl;

        try {
            $redis = $this->redis();
            $redis->multi();

            foreach ($values as $key => $value) {
                if ($ttl > 0) {
                    $redis->setex($this->key($key), $ttl, $value);
                } else {
                    $redis->set($this->key($key), $value);
                }
            }

            $results = $redis->exec();

            return ! in_array(false, $results, true);
        } catch (RedisException $e) {
            $this->logRedisError('Cache putMany', $e, ['keys' => array_keys($values)], 'error');

            return false;
        }
    }

    /**
     * Get the remaining TTL for a key
     */
    public function ttl(string $key): int
    {
        try {
            $ttl = $this->redis()->ttl($this->key($key));

            return $ttl > 0 ? $ttl : 0;
        } catch (RedisException $e) {
            $this->logRedisError('Cache TTL check', $e, ['key' => $key]);

            return 0;
        }
    }

    /**
     * Acquire a lock
     */
    public function lock(string $key, int $seconds = 10): bool
    {
        $lockKey = $this->key('lock:' . $key);

        try {
            // Use SET with NX and EX for atomic lock acquisition
            return (bool) $this->redis()->set(
                $lockKey,
                time(),
                ['NX', 'EX' => $seconds]
            );
        } catch (RedisException $e) {
            $this->logRedisError('Cache lock acquisition', $e, ['key' => $key]);

            return false;
        }
    }

    /**
     * Release a lock
     */
    public function unlock(string $key): bool
    {
        return $this->forget('lock:' . $key);
    }

    /**
     * Clear keys matching a pattern
     */
    public function clearByPattern(string $pattern): int
    {
        try {
            return $this->deleteByPattern($this->key($pattern));
        } catch (RedisException $e) {
            $this->logRedisError('Cache clearByPattern', $e, ['pattern' => $pattern]);

            return 0;
        }
    }

    /**
     * Delete all keys matching a pattern using SCAN (non-blocking)
     *
     * Uses SCAN instead of KEYS to avoid O(N) blocking in production.
     */
    protected function deleteByPattern(string $pattern): int
    {
        $redis = $this->redis();
        $deleted = 0;
        $iterator = null;

        while (($keys = $redis->scan($iterator, $pattern, 100)) !== false) {
            if (! empty($keys)) {
                $deleted += $redis->del(...$keys);
            }
        }

        return $deleted;
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        try {
            $info = $this->redis()->info();

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'uptime_seconds' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (RedisException $e) {
            $this->logRedisError('Cache stats retrieval', $e);

            return [];
        }
    }
}
