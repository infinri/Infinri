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

/**
 * Array Cache Store
 *
 * In-memory cache for testing and single-request caching.
 */
class ArrayStore extends AbstractCacheStore
{
    protected array $storage = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->has($key)) {
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

    public function forget(string $key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    public function has(string $key): bool
    {
        if (! isset($this->storage[$key])) {
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
        if (! is_numeric($current)) {
            return false;
        }
        $new = (int) $current + $value;
        $this->put($key, $new);

        return $new;
    }

    public function flush(): bool
    {
        $this->storage = [];

        return true;
    }
}
