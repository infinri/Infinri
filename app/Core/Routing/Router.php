<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Application;
use App\Core\Contracts\Routing\RouteInterface;
use App\Core\Contracts\Routing\RouterInterface;
use App\Core\Routing\Concerns\RegistersRoutes;
use App\Core\Routing\Concerns\ManagesGroups;
use App\Core\Routing\Concerns\DispatchesRoutes;
use Closure;

/**
 * Router
 * 
 * Enhanced routing system with parameter matching, groups, and middleware support.
 * Uses traits for separation of concerns.
 */
class Router implements RouterInterface
{
    use RegistersRoutes;
    use ManagesGroups;
    use DispatchesRoutes;

    /**
     * All HTTP methods
     */
    protected const ALL_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * The application instance
     */
    protected Application $app;

    /**
     * All registered routes
     * 
     * @var array<int, Route>
     */
    protected array $routes = [];

    /**
     * Named routes for URL generation
     * 
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * URL generator instance
     */
    protected ?UrlGenerator $urlGenerator = null;

    /**
     * Create a new router instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get a route by name
     */
    public function getRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Register a named route
     */
    public function registerNamedRoute(Route $route): void
    {
        $name = $route->getName();
        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
            $this->getUrlGenerator()->register($route);
        }
    }

    /**
     * Generate URL for a named route
     */
    public function url(string $name, array $parameters = [], bool $absolute = false): string
    {
        return $this->getUrlGenerator()->generate($name, $parameters, $absolute);
    }

    /**
     * Get URL generator instance
     */
    public function getUrlGenerator(): UrlGenerator
    {
        if ($this->urlGenerator === null) {
            $baseUrl = config('app.url', 'http://localhost');
            $this->urlGenerator = new UrlGenerator($baseUrl);
            
            // Register existing named routes
            foreach ($this->namedRoutes as $route) {
                $this->urlGenerator->register($route);
            }
        }
        
        return $this->urlGenerator;
    }

    /**
     * Add a route to the collection
     */
    protected function addRoute(array $methods, string $uri, Closure|array|string $action): Route
    {
        $uri = $this->buildRouteUri($uri);
        $route = new Route($methods, $uri, $action);
        
        $this->applyRouteAttributes($route);
        $this->routes[] = $route;
        
        return $route;
    }

    /**
     * Build route URI with prefixes
     */
    protected function buildRouteUri(string $uri): string
    {
        $uri = $this->applyGroupPrefix($uri);
        
        if ($this->pendingPrefix !== null) {
            $uri = '/' . trim($this->pendingPrefix, '/') . '/' . ltrim($uri, '/');
        }
        
        return $uri;
    }

    /**
     * Apply attributes to a route
     */
    protected function applyRouteAttributes(Route $route): void
    {
        // Apply group middleware
        $groupMiddleware = $this->getGroupMiddleware();
        if (!empty($groupMiddleware)) {
            $route->middleware($groupMiddleware);
        }
        
        // Apply pending middleware
        if (!empty($this->pendingMiddleware)) {
            $route->middleware($this->pendingMiddleware);
        }
        
        // Apply group name prefix
        $groupNamePrefix = $this->getGroupNamePrefix();
        if ($groupNamePrefix !== '') {
            $route->setNamePrefix($groupNamePrefix);
        }
        
        // Apply pending name prefix
        if ($this->pendingNamePrefix !== null) {
            $route->setNamePrefix($this->pendingNamePrefix);
        }
        
        // Consume pending attributes
        $this->consumePendingAttributes();
    }
}
