<?php

declare(strict_types=1);

namespace App\Core\Cache;

use App\Core\Contracts\Cache\CacheInterface;

/**
 * Cache Manager
 * 
 * Manages cache stores and provides a unified interface.
 */
class CacheManager implements CacheInterface
{
    /**
     * The default store name
     */
    protected string $default = 'file';

    /**
     * Resolved cache stores
     * @var array<string, CacheInterface>
     */
    protected array $stores = [];

    /**
     * Store configurations
     */
    protected array $config;

    /**
     * Base path for cache directories
     */
    protected string $basePath;

    /**
     * Named cache pools
     */
    protected const POOLS = ['runtime', 'views', 'data'];

    public function __construct(array $config = [], ?string $basePath = null)
    {
        $this->config = $config;
        $this->default = $config['default'] ?? 'file';
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Get a named pool (runtime, views, data)
     */
    public function pool(string $name): CacheInterface
    {
        if (!in_array($name, self::POOLS)) {
            throw new \InvalidArgumentException("Unknown cache pool: {$name}");
        }

        $storeKey = "pool:{$name}";

        if (!isset($this->stores[$storeKey])) {
            $this->stores[$storeKey] = new FileStore(
                $this->basePath . '/var/cache/' . $name,
                $this->config['stores']['file']['ttl'] ?? 3600
            );
        }

        return $this->stores[$storeKey];
    }

    /**
     * Clear a specific pool
     */
    public function clearPool(string $name): bool
    {
        return $this->pool($name)->flush();
    }

    /**
     * Clear all pools
     */
    public function clearAllPools(): array
    {
        $results = [];
        foreach (self::POOLS as $pool) {
            $results[$pool] = $this->clearPool($pool);
        }
        return $results;
    }

    /**
     * Get available pool names
     */
    public function getPoolNames(): array
    {
        return self::POOLS;
    }

    /**
     * Get a cache store instance
     */
    public function store(?string $name = null): CacheInterface
    {
        $name = $name ?? $this->default;

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->resolve($name);
        }

        return $this->stores[$name];
    }

    /**
     * Resolve a cache store
     */
    protected function resolve(string $name): CacheInterface
    {
        $config = $this->config['stores'][$name] ?? [];
        $driver = $config['driver'] ?? 'file';

        return match ($driver) {
            'file' => new FileStore(
                $config['path'] ?? sys_get_temp_dir() . '/cache',
                $config['ttl'] ?? 3600
            ),
            'array' => new ArrayStore(),
            default => throw new \InvalidArgumentException("Unsupported cache driver: {$driver}"),
        };
    }

    // Proxy methods to default store

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->store()->put($key, $value, $ttl);
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->store()->add($key, $value, $ttl);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->store()->forever($key, $value);
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return $this->store()->remember($key, $ttl, $callback);
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->store()->rememberForever($key, $callback);
    }

    public function forget(string $key): bool
    {
        return $this->store()->forget($key);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->store()->increment($key, $value);
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->store()->decrement($key, $value);
    }

    public function flush(): bool
    {
        return $this->store()->flush();
    }

    public function many(array $keys): array
    {
        return $this->store()->many($keys);
    }

    public function putMany(array $values, ?int $ttl = null): bool
    {
        return $this->store()->putMany($values, $ttl);
    }
}
