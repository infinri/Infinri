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
namespace App\Core\Contracts\Config;

/**
 * Configuration Interface
 * 
 * Provides access to application configuration
 */
interface ConfigInterface
{
    /**
     * Determine if a configuration value exists
     *
     * @param string $key The configuration key (supports dot notation)
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get a configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value
     *
     * @param string|array $key The configuration key or array of key-value pairs
     * @param mixed $value The value to set (ignored if $key is array)
     * @return void
     */
    public function set(string|array $key, mixed $value = null): void;

    /**
     * Get all configuration values
     *
     * @return array
     */
    public function all(): array;
}
