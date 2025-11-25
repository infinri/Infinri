<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Contracts\Config\ConfigInterface;

/**
 * Configuration Repository
 * 
 * Manages application configuration with dot notation support
 */
class Config implements ConfigInterface
{
    /**
     * All of the configuration items
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Create a new configuration repository
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        // Support dot notation (e.g., 'database.default')
        if (!str_contains($key, '.')) {
            return $default;
        }

        $array = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            $this->setItem($k, $v);
        }
    }

    /**
     * Set a configuration item using dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setItem(string $key, mixed $value): void
    {
        // If no dot notation, set directly
        if (!str_contains($key, '.')) {
            $this->items[$key] = $value;
            return;
        }

        // Support dot notation for nested arrays
        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
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
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function prepend(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }
}
