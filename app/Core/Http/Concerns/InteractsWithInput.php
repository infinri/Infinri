<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Http\Concerns;

/**
 * Provides input handling methods for Request
 * 
 * Extracts input-related functionality to keep Request focused on
 * core HTTP request representation (SRP).
 */
trait InteractsWithInput
{
    /**
     * Get input value from query or body
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->query->get($key) 
            ?? $this->request->get($key) 
            ?? $this->getJsonInput($key) 
            ?? $default;
    }

    /**
     * Get query string parameter
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query->get($key, $default);
    }

    /**
     * Get POST body parameter
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->request->get($key, $default);
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return array_merge(
            $this->query->all(),
            $this->request->all(),
            $this->getJsonInputAll()
        );
    }

    /**
     * Get subset of input data
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get all input except specified keys
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Check if input key exists
     */
    public function has(string $key): bool
    {
        return $this->query->has($key) 
            || $this->request->has($key) 
            || array_key_exists($key, $this->getJsonInputAll());
    }

    /**
     * Check if input exists and is not empty
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Get boolean input
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get integer input
     */
    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->input($key, $default);
    }

    /**
     * Get string input
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Get JSON input for a key
     */
    protected function getJsonInput(string $key): mixed
    {
        if (!$this->isJson()) {
            return null;
        }
        
        return $this->getJsonInputAll()[$key] ?? null;
    }

    /**
     * Cached JSON input
     */
    protected ?array $jsonInputCache = null;

    /**
     * Get all JSON input (cached per instance)
     */
    protected function getJsonInputAll(): array
    {
        if ($this->jsonInputCache !== null) {
            return $this->jsonInputCache;
        }
        
        if (!$this->isJson() || $this->content === null) {
            return $this->jsonInputCache = [];
        }
        
        $data = json_decode($this->content, true);
        
        return $this->jsonInputCache = is_array($data) ? $data : [];
    }
}
