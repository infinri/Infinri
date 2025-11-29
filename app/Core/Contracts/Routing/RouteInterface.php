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
namespace App\Core\Contracts\Routing;

/**
 * Route Interface
 * 
 * Contract for a single route definition
 */
interface RouteInterface
{
    /**
     * Get the route URI pattern
     */
    public function getUri(): string;

    /**
     * Get the route HTTP methods
     */
    public function getMethods(): array;

    /**
     * Get the route action
     */
    public function getAction(): mixed;

    /**
     * Get the route name
     */
    public function getName(): ?string;

    /**
     * Set the route name
     */
    public function name(string $name): static;

    /**
     * Get route middleware
     */
    public function getMiddleware(): array;

    /**
     * Add middleware to the route
     */
    public function middleware(string|array $middleware): static;

    /**
     * Get parameter constraints
     */
    public function getWheres(): array;

    /**
     * Add parameter constraint
     */
    public function where(string|array $name, ?string $expression = null): static;

    /**
     * Check if route matches the given request path and method
     */
    public function matches(string $path, string $method): bool;

    /**
     * Get extracted parameters from the matched path
     */
    public function getParameters(): array;

    /**
     * Get the compiled regex pattern
     */
    public function getCompiledPattern(): string;
}
