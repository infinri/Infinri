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
namespace App\Core\Cache;

use App\Core\Contracts\Cache\CacheInterface;

/**
 * File Cache Store
 * 
 * File-based cache implementation.
 */
class FileStore implements CacheInterface
{
    /**
     * Cache directory path
     */
    protected string $path;

    /**
     * Default TTL in seconds
     */
    protected int $defaultTtl;

    public function __construct(string $path, int $defaultTtl = 3600)
    {
        $this->path = rtrim($path, '/');
        $this->defaultTtl = $defaultTtl;

        ensure_directory($this->path);
    }

    /**
     * Get an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->getPayload($key);

        if ($payload === null) {
            $this->recordCacheMetric(false);
            return $default;
        }

        $this->recordCacheMetric(true);
        return $payload['data'];
    }

    /**
     * Record cache hit/miss metric
     */
    protected function recordCacheMetric(bool $hit): void
    {
        if (class_exists(\App\Core\Metrics\MetricsCollector::class)) {
            try {
                (new \App\Core\Metrics\MetricsCollector())->recordCache($hit);
            } catch (\Throwable) {
                // Don't let metrics recording break cache operations
            }
        }
    }

    /**
     * Store an item in the cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiration = $ttl > 0 ? time() + $ttl : 0;

        $payload = [
            'data' => $value,
            'expiration' => $expiration,
        ];

        $path = $this->getPath($key);
        ensure_directory(dirname($path));

        return file_put_contents($path, serialize($payload), LOCK_EX) !== false;
    }

    /**
     * Store an item in the cache if it doesn't exist
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache forever
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Get an item from cache, or store the default value
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Get an item from cache, or store forever
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, 0, $callback);
    }

    /**
     * Remove an item from the cache
     */
    public function forget(string $key): bool
    {
        $path = $this->getPath($key);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool
    {
        return $this->getPayload($key) !== null;
    }

    /**
     * Increment the value of an item
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $current = $this->get($key, 0);

        if (!is_numeric($current)) {
            return false;
        }

        $new = (int) $current + $value;
        $this->put($key, $new);

        return $new;
    }

    /**
     * Decrement the value of an item
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    /**
     * Clear all items from the cache
     */
    public function flush(): bool
    {
        if (!is_dir($this->path)) {
            return true;
        }

        return clear_directory($this->path, false);
    }

    /**
     * Get multiple items from the cache
     */
    public function many(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    /**
     * Store multiple items in the cache
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get the full path for a cache key
     */
    protected function getPath(string $key): string
    {
        $hash = sha1($key);
        $parts = array_slice(str_split($hash, 2), 0, 2);

        return $this->path . '/' . implode('/', $parts) . '/' . $hash;
    }

    /**
     * Get payload from cache file
     */
    protected function getPayload(string $key): ?array
    {
        $path = $this->getPath($key);

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $payload = @unserialize($contents);

        if ($payload === false || !is_array($payload)) {
            $this->forget($key);
            return null;
        }

        // Check expiration
        if ($payload['expiration'] !== 0 && $payload['expiration'] < time()) {
            $this->forget($key);
            return null;
        }

        return $payload;
    }
}
