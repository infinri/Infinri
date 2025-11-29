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
namespace App\Core\Contracts\Container;

use Closure;

/**
 * Container Interface - PSR-11 inspired
 * 
 * Defines the contract for the IoC container
 */
interface ContainerInterface
{
    /**
     * Bind a type to the container
     *
     * @param string $abstract The abstract type (interface or class name)
     * @param Closure|string|null $concrete The concrete implementation
     * @param bool $singleton Whether to bind as singleton
     * @return void
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $singleton = false): void;

    /**
     * Register a singleton binding
     *
     * @param string $abstract The abstract type
     * @param Closure|string|null $concrete The concrete implementation
     * @return void
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register an existing instance as singleton
     *
     * @param string $abstract The abstract type
     * @param mixed $instance The instance to register
     * @return mixed The instance
     */
    public function instance(string $abstract, mixed $instance): mixed;

    /**
     * Resolve a type from the container
     *
     * @param string $abstract The abstract type to resolve
     * @param array $parameters Optional parameters for construction
     * @return mixed The resolved instance
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Alias a type to another name
     *
     * @param string $abstract The original type
     * @param string $alias The alias name
     * @return void
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Determine if a type has been bound
     *
     * @param string $abstract The type to check
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Determine if a type has been resolved
     *
     * @param string $abstract The type to check
     * @return bool
     */
    public function resolved(string $abstract): bool;
}
