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
namespace App\Core\Routing;

use InvalidArgumentException;

/**
 * URL Generator
 *
 * Generates URLs from named routes.
 * Single Responsibility: URL generation only.
 */
class UrlGenerator
{
    /**
     * Named routes registry
     *
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * Base URL for absolute URLs
     */
    protected string $baseUrl;

    public function __construct(string $baseUrl = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Register a named route
     */
    public function register(Route $route): void
    {
        $name = $route->getName();
        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Get a route by name
     */
    public function getRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Check if a named route exists
     */
    public function has(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * Generate URL for a named route
     */
    public function generate(string $name, array $parameters = [], bool $absolute = false): string
    {
        $route = $this->namedRoutes[$name] ?? null;

        if ($route === null) {
            throw new InvalidArgumentException("Route [{$name}] not defined.");
        }

        return $this->buildUrl($route, $parameters, $absolute);
    }

    /**
     * Build URL from route and parameters
     */
    protected function buildUrl(Route $route, array $parameters, bool $absolute): string
    {
        $uri = $route->getUri();
        $routeParams = $route->getParameterNames();
        $queryParams = [];

        // Replace route parameters
        foreach ($parameters as $key => $value) {
            if (in_array($key, $routeParams, true)) {
                $uri = preg_replace("/\{{$key}\??}/", (string) $value, $uri);
            } else {
                $queryParams[$key] = $value;
            }
        }

        // Remove unfilled optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        // Clean up double slashes
        $uri = preg_replace('#/+#', '/', $uri);

        // Add query string for extra parameters
        if (! empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        return $absolute ? $this->baseUrl . $uri : $uri;
    }

    /**
     * Set base URL
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
}
