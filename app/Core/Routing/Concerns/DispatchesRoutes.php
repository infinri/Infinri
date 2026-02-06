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
namespace App\Core\Routing\Concerns;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\JsonResponse;
use App\Core\Http\Response;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use App\Core\Routing\Route;
use App\Core\Support\Str;
use Closure;
use JsonSerializable;
use RuntimeException;

/**
 * Provides route dispatching functionality
 *
 * Single responsibility: Match routes and execute actions
 */
trait DispatchesRoutes
{
    /**
     * The currently matched route
     */
    protected ?Route $currentRoute = null;

    /**
     * Dispatch request to matching route
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $path = $request->path();
        $method = $request->method();

        $route = $this->findRoute($path, $method);

        if ($route === null) {
            $allowedMethods = $this->getAllowedMethods($path);

            if (! empty($allowedMethods)) {
                throw new MethodNotAllowedException($path, $method, $allowedMethods);
            }

            throw new RouteNotFoundException($path, $method);
        }

        $this->currentRoute = $route;
        $request->setRouteParameters($route->getParameters());

        return $this->runRoute($route, $request);
    }

    /**
     * Get the current route
     */
    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    /**
     * Find a matching route using indexed lookups
     *
     * Lookup strategy:
     * 1. Static routes: O(1) hash lookup by METHOD:path
     * 2. Dynamic routes: O(k) where k = routes with same first segment
     * 3. Fallback: O(n) linear scan (only for edge cases)
     */
    protected function findRoute(string $path, string $method): ?Route
    {
        $method = strtoupper($method);
        $normalizedPath = Str::normalizeUri($path);

        // 1. Try static route lookup - O(1)
        $staticKey = $method . ':' . $normalizedPath;
        if (isset($this->staticRoutes[$staticKey])) {
            $route = $this->staticRoutes[$staticKey];
            // Static routes always match, but call matches() to set parameters
            $route->matches($normalizedPath, $method);

            return $route;
        }

        // 2. Try dynamic route lookup by first segment - O(k)
        $firstSegment = explode('/', trim($normalizedPath, '/'))[0] ?? '';
        if (isset($this->dynamicRoutes[$method][$firstSegment])) {
            foreach ($this->dynamicRoutes[$method][$firstSegment] as $route) {
                if ($route->matches($normalizedPath, $method)) {
                    return $route;
                }
            }
        }

        // 3. Fallback: check dynamic routes with empty/wildcard first segment - O(m)
        // This handles routes like /{slug} that match any first segment
        if (isset($this->dynamicRoutes[$method][''])) {
            foreach ($this->dynamicRoutes[$method][''] as $route) {
                if ($route->matches($normalizedPath, $method)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Get allowed methods for a path using indexed lookups
     */
    protected function getAllowedMethods(string $path): array
    {
        $methods = [];
        $normalizedPath = Str::normalizeUri($path);
        $firstSegment = explode('/', trim($normalizedPath, '/'))[0] ?? '';

        // Check static routes for all methods
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $staticKey = $method . ':' . $normalizedPath;
            if (isset($this->staticRoutes[$staticKey])) {
                $methods[] = $method;
            }
        }

        // Check dynamic routes by first segment
        foreach ($this->dynamicRoutes as $method => $segmentRoutes) {
            if (isset($segmentRoutes[$firstSegment])) {
                foreach ($segmentRoutes[$firstSegment] as $route) {
                    if ($route->matches($normalizedPath, $method)) {
                        $methods = array_merge($methods, $route->getMethods());
                        break;
                    }
                }
            }
            // Also check wildcard routes
            if (isset($segmentRoutes[''])) {
                foreach ($segmentRoutes[''] as $route) {
                    if ($route->matches($normalizedPath, $method)) {
                        $methods = array_merge($methods, $route->getMethods());
                        break;
                    }
                }
            }
        }

        return array_unique($methods);
    }

    /**
     * Run the route action
     */
    protected function runRoute(Route $route, RequestInterface $request): ResponseInterface
    {
        $action = $route->getAction();
        $result = $this->executeAction($action, $request);

        return $this->prepareResponse($result);
    }

    /**
     * Execute a route action
     */
    protected function executeAction(Closure|array|string $action, RequestInterface $request): mixed
    {
        if ($action instanceof Closure) {
            return $this->app->call($action, ['request' => $request]);
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            $instance = $this->app->make($controller);

            return $this->app->call([$instance, $method], ['request' => $request]);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action, 2);
            $instance = $this->app->make($controller);

            return $this->app->call([$instance, $method], ['request' => $request]);
        }

        $error = 'Invalid route action type: ' . gettype($action);
        if (function_exists('logger')) {
            logger()->error('Invalid route action', [
                'action_type' => gettype($action),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
        }
        throw new RuntimeException($error);
    }

    /**
     * Prepare the response from route action result
     */
    protected function prepareResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_array($result) || $result instanceof JsonSerializable) {
            return new JsonResponse($result);
        }

        if (is_scalar($result)) {
            return new Response((string) $result);
        }

        if ($result === null) {
            return new Response('');
        }

        $error = 'Invalid response type returned from route action: ' . gettype($result);
        if (function_exists('logger')) {
            logger()->error('Invalid route response', [
                'response_type' => gettype($result),
                'route' => $this->currentRoute?->getUri(),
            ]);
        }
        throw new RuntimeException($error);
    }
}
