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
namespace App\Core\Routing;

use App\Core\Module\ModuleRenderer;

/**
 * Simple Router
 * 
 * Lightweight router for module-based routing.
 * Provides fluent API for defining routes to modules.
 */
class SimpleRouter
{
    /**
     * Registered routes
     */
    protected array $routes = [];

    /**
     * Current request method
     */
    protected string $method;

    /**
     * Current request path
     */
    protected string $path;

    /**
     * Module renderer
     */
    protected ModuleRenderer $renderer;

    public function __construct(?ModuleRenderer $renderer = null)
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->renderer = $renderer ?? new ModuleRenderer();
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable|string $handler): static
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|string $handler): static
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|string $handler): static
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $path, callable|string $handler): static
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|string $handler): static
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register a route for any method
     */
    public function any(string $path, callable|string $handler): static
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Add a route
     */
    protected function addRoute(string $method, string $path, callable|string $handler): static
    {
        $this->routes[$method][$path] = $handler;
        return $this;
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(string $notFoundHandler = 'error'): void
    {
        $handler = $this->routes[$this->method][$this->path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            $this->execute($notFoundHandler);
            return;
        }

        $this->execute($handler);
    }

    /**
     * Execute a handler
     */
    protected function execute(callable|string $handler): void
    {
        if (is_callable($handler)) {
            $handler();
        } else {
            $this->renderer->render($handler);
        }
    }

    /**
     * Render an error page
     */
    public function renderError(int $code, ?string $type = null): void
    {
        $this->renderer->renderError($code, $type);
    }

    /**
     * Render maintenance page
     */
    public function renderMaintenance(): void
    {
        $this->renderer->renderMaintenance();
    }

    /**
     * Get the module renderer
     */
    public function getRenderer(): ModuleRenderer
    {
        return $this->renderer;
    }
}
