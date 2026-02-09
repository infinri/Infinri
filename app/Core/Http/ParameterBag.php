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
namespace App\Core\Http;

/**
 * Parameter Bag
 *
 * Container for request parameters with convenient access methods
 */
class ParameterBag extends AbstractBag
{
    /**
     * Create a new parameter bag
     *
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->items = $parameters;
    }

    /**
     * Replace all parameters
     *
     * @param array<string, mixed> $parameters
     */
    public function replace(array $parameters): void
    {
        $this->items = $parameters;
    }

    /**
     * Add parameters (merge with existing)
     *
     * @param array<string, mixed> $parameters
     */
    public function add(array $parameters): void
    {
        $this->items = array_merge($this->items, $parameters);
    }

    /**
     * Get a parameter value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Set a parameter value
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Check if parameter exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Remove a parameter
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Get parameter as string
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Get parameter as integer
     *
     * @param string $key
     * @param int $default
     *
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Get parameter as boolean
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
