<?php

declare(strict_types=1);

namespace App\Core\Routing\Concerns;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Routing\Route;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use Closure;

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
            
            if (!empty($allowedMethods)) {
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
     * Find a matching route
     */
    protected function findRoute(string $path, string $method): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($path, $method)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Get allowed methods for a path
     */
    protected function getAllowedMethods(string $path): array
    {
        $methods = [];
        
        foreach ($this->routes as $route) {
            foreach ($route->getMethods() as $method) {
                if ($route->matches($path, $method)) {
                    $methods = array_merge($methods, $route->getMethods());
                    break;
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
        throw new \RuntimeException($error);
    }

    /**
     * Prepare the response from route action result
     */
    protected function prepareResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        
        if (is_array($result) || $result instanceof \JsonSerializable) {
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
        throw new \RuntimeException($error);
    }
}
