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

use Throwable;

/**
 * File Cache Store
 *
 * File-based cache implementation.
 */
class FileStore extends AbstractCacheStore
{
    public function __construct(
        protected string $path,
        protected int $defaultTtl = 3600
    ) {
        $this->path = rtrim($path, '/');
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
                new \App\Core\Metrics\MetricsCollector()->recordCache($hit);
            } catch (Throwable) {
                // Don't let metrics recording break cache operations
            }
        }
    }

    /**
     * Store an item in the cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= $this->defaultTtl;
        $expiration = $ttl > 0 ? time() + $ttl : 0;

        $payload = [
            'data' => $value,
            'expiration' => $expiration,
        ];

        $path = $this->getPath($key);
        ensure_directory(dirname($path));

        return file_put_contents($path, serialize($payload), LOCK_EX) !== false;
    }

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

        if (! is_numeric($current)) {
            return false;
        }

        $new = (int) $current + $value;
        $this->put($key, $new);

        return $new;
    }

    public function flush(): bool
    {
        if (! is_dir($this->path)) {
            return true;
        }

        return clear_directory($this->path, false);
    }

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

        if (! file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $payload = @unserialize($contents);

        if ($payload === false || ! is_array($payload)) {
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
