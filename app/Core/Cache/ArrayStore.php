<?php

declare(strict_types=1);

namespace App\Core\Cache;

use App\Core\Contracts\Cache\CacheInterface;

/**
 * Array Cache Store
 * 
 * In-memory cache for testing and single-request caching.
 */
class ArrayStore implements CacheInterface
{
    /**
     * Cached items
     */
    protected array $storage = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->storage[$key]['value'];
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiration' => $ttl ? time() + $ttl : 0,
        ];

        return true;
    }

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

    public function forget(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return false;
        }

        $item = $this->storage[$key];

        if ($item['expiration'] !== 0 && $item['expiration'] < time()) {
            $this->forget($key);
            return false;
        }

        return true;
    }

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

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    public function flush(): bool
    {
        $this->storage = [];
        return true;
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
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }

        return true;
    }
}
