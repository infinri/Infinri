<?php

declare(strict_types=1);

namespace App\Core\Contracts\Routing;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

/**
 * Router Interface
 * 
 * Contract for HTTP routing
 */
interface RouterInterface
{
    /**
     * Register a GET route
     */
    public function get(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a POST route
     */
    public function post(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a PUT route
     */
    public function put(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a PATCH route
     */
    public function patch(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a route for multiple methods
     */
    public function match(array $methods, string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Register a route for all methods
     */
    public function any(string $uri, Closure|array|string $action): RouteInterface;

    /**
     * Create a route group
     */
    public function group(array $attributes, Closure $callback): void;

    /**
     * Add middleware to routes
     */
    public function middleware(string|array $middleware): static;

    /**
     * Add prefix to routes
     */
    public function prefix(string $prefix): static;

    /**
     * Dispatch request to matching route
     */
    public function dispatch(RequestInterface $request): ResponseInterface;

    /**
     * Get all registered routes
     */
    public function getRoutes(): array;
}
