<?php
declare(strict_types=1);
/**
 * Cache Helper
 *
 * Simple in-memory caching for runtime performance
 *
 * @package App\Helpers
 */

namespace App\Helpers;

class Cache
{
    /**
     * Cache storage
     *
     * @var array
     */
    private static array $cache = [];

    /**
     * Get or compute cached value
     *
     * @param string $key Cache key
     * @param callable $callback Callback to compute value if not cached
     * @return mixed
     */
    public static function remember(string $key, callable $callback)
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = $callback();
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /**
     * Clear cache by prefix
     *
     * @param string $prefix Key prefix to match
     * @return void
     */
    public static function clearPrefix(string $prefix): void
    {
        foreach (array_keys(self::$cache) as $key) {
            if (strpos($key, $prefix) === 0) {
                unset(self::$cache[$key]);
            }
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function stats(): array
    {
        return [
            'count' => count(self::$cache),
            'keys' => array_keys(self::$cache),
            'size' => strlen(serialize(self::$cache))
        ];
    }
}
