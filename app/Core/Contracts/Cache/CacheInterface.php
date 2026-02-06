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
namespace App\Core\Contracts\Cache;

/**
 * Cache Contract
 *
 * Defines the caching interface for the application.
 */
interface CacheInterface
{
    /**
     * Get an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache if it doesn't exist
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache forever
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Get an item from cache, or store the default value
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed;

    /**
     * Get an item from cache, or store forever
     */
    public function rememberForever(string $key, callable $callback): mixed;

    /**
     * Remove an item from the cache
     */
    public function forget(string $key): bool;

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool;

    /**
     * Increment the value of an item
     */
    public function increment(string $key, int $value = 1): int|bool;

    /**
     * Decrement the value of an item
     */
    public function decrement(string $key, int $value = 1): int|bool;

    /**
     * Clear all items from the cache
     */
    public function flush(): bool;

    /**
     * Get multiple items from the cache
     */
    public function many(array $keys): array;

    /**
     * Store multiple items in the cache
     */
    public function putMany(array $values, ?int $ttl = null): bool;
}
