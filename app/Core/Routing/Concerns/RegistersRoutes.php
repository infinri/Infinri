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
namespace App\Core\Routing\Concerns;

use App\Core\Contracts\Routing\RouteInterface;
use App\Core\Routing\Route;
use Closure;

/**
 * Provides HTTP method route registration
 * 
 * Single responsibility: Route registration shortcuts
 */
trait RegistersRoutes
{
    /**
     * Register a GET route
     */
    public function get(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register an OPTIONS route
     */
    public function options(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Register a route for multiple methods
     */
    public function match(array $methods, string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute($methods, $uri, $action);
    }

    /**
     * Register a route for all methods
     */
    public function any(string $uri, Closure|array|string $action): RouteInterface
    {
        return $this->addRoute(self::ALL_METHODS, $uri, $action);
    }

    /**
     * Register a RESTful resource controller
     * 
     * Creates the following routes:
     * - GET    /resource           -> index
     * - GET    /resource/create    -> create
     * - POST   /resource           -> store
     * - GET    /resource/{id}      -> show
     * - GET    /resource/{id}/edit -> edit
     * - PUT    /resource/{id}      -> update
     * - PATCH  /resource/{id}      -> update
     * - DELETE /resource/{id}      -> destroy
     * 
     * @param string $name Resource name (e.g., 'users')
     * @param string $controller Controller class name
     * @param array $options Options: 'only', 'except', 'parameters'
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $parameter = $options['parameters'][$name] ?? 'id';
        
        $resourceActions = [
            'index'   => ['GET', "/{$name}", 'index'],
            'create'  => ['GET', "/{$name}/create", 'create'],
            'store'   => ['POST', "/{$name}", 'store'],
            'show'    => ['GET', "/{$name}/{{$parameter}}", 'show'],
            'edit'    => ['GET', "/{$name}/{{$parameter}}/edit", 'edit'],
            'update'  => [['PUT', 'PATCH'], "/{$name}/{{$parameter}}", 'update'],
            'destroy' => ['DELETE', "/{$name}/{{$parameter}}", 'destroy'],
        ];
        
        // Filter based on 'only' or 'except'
        if (isset($options['only'])) {
            $resourceActions = array_intersect_key($resourceActions, array_flip($options['only']));
        } elseif (isset($options['except'])) {
            $resourceActions = array_diff_key($resourceActions, array_flip($options['except']));
        }
        
        // Register routes
        foreach ($resourceActions as $action => [$methods, $uri, $method]) {
            $methods = is_array($methods) ? $methods : [$methods];
            $this->addRoute($methods, $uri, [$controller, $method])->name("{$name}.{$action}");
        }
    }

    /**
     * Register an API resource (without create/edit form routes)
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        $this->resource($name, $controller, $options);
    }

    /**
     * Add a route to the collection (abstract - must be implemented by using class)
     */
    abstract protected function addRoute(array $methods, string $uri, Closure|array|string $action): Route;
}
