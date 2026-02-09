<?php declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Contracts\Cache\CacheInterface;

class MockCache implements CacheInterface
{
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->store[$key] = $value;

        return true;
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (! isset($this->store[$key])) {
            $this->store[$key] = $value;

            return true;
        }

        return false;
    }

    public function forget(string $key): bool
    {
        unset($this->store[$key]);

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }

    public function flush(): bool
    {
        $this->store = [];

        return true;
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function putMany(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }

        return true;
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        $current = (int) $this->get($key, 0);
        $this->put($key, $current + $value);

        return $current + $value;
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
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
        return $this->remember($key, null, $callback);
    }
}
