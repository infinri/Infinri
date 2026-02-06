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

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Parameter Bag
 *
 * Container for request parameters with convenient access methods
 */
class ParameterBag implements IteratorAggregate, Countable
{
    /**
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Create a new parameter bag
     *
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Get all parameters
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Get parameter keys
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Replace all parameters
     *
     * @param array<string, mixed> $parameters
     */
    public function replace(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Add parameters (merge with existing)
     *
     * @param array<string, mixed> $parameters
     */
    public function add(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
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
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Set a parameter value
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
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
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Remove a parameter
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
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

    /**
     * Get iterator
     *
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->parameters);
    }

    /**
     * Get count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }
}
