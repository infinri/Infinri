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
    public function __construct(
        protected RedisManager $redis,
        protected string $connection = 'cache',
        protected int $defaultTtl = 3600,
        protected string $prefix = 'cache:'
    ) {
    }

    /**
     * Get the Redis connection
     */
    protected function redis(): Redis
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the prefixed key
     */
    protected function key(string $key): string
    {
        return $this->prefix . $key;
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
            logger()->warning('Cache get failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->error('Cache put failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->error('Cache add failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->error('Cache forever failed', ['key' => $key, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            return $this->redis()->del($this->key($key)) > 0;
        } catch (RedisException $e) {
            logger()->warning('Cache forget failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->warning('Cache has check failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->warning('Cache increment failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->warning('Cache decrement failed', ['key' => $key, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Flush all items from the cache
     */
    public function flush(): bool
    {
        try {
            // Only flush keys with our prefix
            $keys = $this->redis()->keys($this->prefix . '*');

            if (! empty($keys)) {
                // Remove the global prefix that Redis might add
                $this->redis()->del(...$keys);
            }

            return true;
        } catch (RedisException $e) {
            logger()->error('Cache flush failed', ['error' => $e->getMessage()]);

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
            logger()->warning('Cache many get failed', ['keys' => $keys, 'error' => $e->getMessage()]);

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
            logger()->error('Cache putMany failed', ['keys' => array_keys($values), 'error' => $e->getMessage()]);

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
            logger()->warning('Cache TTL check failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            logger()->warning('Cache lock acquisition failed', ['key' => $key, 'error' => $e->getMessage()]);

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
            $keys = $this->redis()->keys($this->key($pattern));

            if (empty($keys)) {
                return 0;
            }

            return $this->redis()->del(...$keys);
        } catch (RedisException $e) {
            logger()->warning('Cache clearByPattern failed', ['pattern' => $pattern, 'error' => $e->getMessage()]);

            return 0;
        }
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
            logger()->warning('Cache stats retrieval failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
