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

use App\Core\Contracts\Cache\CacheInterface;

/**
 * Abstract Cache Store
 *
 * Provides common method implementations for cache stores.
 * Subclasses only need to implement the core operations: get, put, forget, has, increment, flush.
 */
abstract class AbstractCacheStore implements CacheInterface
{
    abstract public function get(string $key, mixed $default = null): mixed;
    abstract public function put(string $key, mixed $value, ?int $ttl = null): bool;
    abstract public function forget(string $key): bool;
    abstract public function has(string $key): bool;
    abstract public function increment(string $key, int $value = 1): int|bool;
    abstract public function flush(): bool;

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $ttl);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, 0, $callback);
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    public function many(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    public function putMany(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (! $this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }
}
