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
namespace App\Core\Config;

use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Support\Arr;

/**
 * Configuration Repository
 *
 * Manages application configuration with dot notation support.
 * Uses CompiledConfig for O(1) static access when available.
 */
class Config implements ConfigInterface
{
    protected array $items = [];

    /**
     * Whether CompiledConfig is available for fast static access
     */
    private static ?bool $staticConfigAvailable = null;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        // First: Check for dynamically set values (runtime overrides)
        if (isset($this->items['_flat'][$key])) {
            return true;
        }

        // Check nested items
        if (Arr::has($this->items, $key)) {
            return true;
        }

        // Fallback: Try static config (O(1), OPcache optimized)
        if (self::isStaticConfigAvailable()) {
            return CompiledConfig::has($key);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Uses CompiledConfig for O(1) static lookup when available.
     * Falls back to array lookup for dynamic/runtime config.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // First: Check for dynamically set values (runtime overrides)
        // This handles config()->set() calls that modify the instance
        if (isset($this->items['_flat'][$key])) {
            return $this->items['_flat'][$key];
        }

        // Check nested items for dynamically set nested values
        $value = Arr::get($this->items, $key);
        if ($value !== null) {
            return $value;
        }

        // Fast path: Use static compiled config (O(1), OPcache optimized)
        // This is a single opcode - FETCH_STATIC_PROP_R
        if (self::isStaticConfigAvailable()) {
            return CompiledConfig::get($key, $default);
        }

        return $default;
    }

    /**
     * Check if CompiledConfig class is available
     */
    private static function isStaticConfigAvailable(): bool
    {
        if (self::$staticConfigAvailable === null) {
            self::$staticConfigAvailable = class_exists(CompiledConfig::class, false)
                || (file_exists(base_path('var/cache/CompiledConfig.php'))
                    && (require_once base_path('var/cache/CompiledConfig.php')) !== false);
        }

        return self::$staticConfigAvailable;
    }

    /**
     * Reset static config availability check (useful for testing)
     */
    public static function resetStaticConfig(): void
    {
        self::$staticConfigAvailable = null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            Arr::set($this->items, $k, $v);

            // Also update _flat for O(1) access on dynamic values
            if (! isset($this->items['_flat'])) {
                $this->items['_flat'] = [];
            }
            $this->items['_flat'][$k] = $v;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Prepend a value onto an array configuration value
     */
    public function prepend(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        array_unshift($array, $value);
        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->set($key, $array);
    }
}
