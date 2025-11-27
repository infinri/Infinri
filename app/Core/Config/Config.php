<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Support\Arr;

/**
 * Configuration Repository
 * 
 * Manages application configuration with dot notation support.
 * Uses Arr helper for centralized array operations.
 */
class Config implements ConfigInterface
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            Arr::set($this->items, $k, $v);
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
