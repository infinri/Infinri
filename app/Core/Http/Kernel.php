<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Application;
use App\Core\Contracts\Http\KernelInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Contracts\Routing\RouterInterface;
use App\Core\Http\Middleware\Pipeline;
use Throwable;

/**
 * HTTP Kernel
 * 
 * Manages the HTTP request lifecycle including middleware execution,
 * routing, and response sending. Delegates exception handling to ExceptionHandler.
 */
class Kernel implements KernelInterface
{
    protected Application $app;
    protected RouterInterface $router;
    protected ExceptionHandler $exceptionHandler;
    protected float $requestStartTime;

    /** @var array<int, string> */
    protected array $middleware = [];

    /** @var array<string, string> */
    protected array $routeMiddleware = [];

    /** @var array<string, array<int, string>> */
    protected array $middlewareGroups = [];

    public function __construct(Application $app, RouterInterface $router)
    {
        $this->app = $app;
        $this->router = $router;
        $this->exceptionHandler = new ExceptionHandler(config('app.debug', false));
    }

    /**
     * Handle an incoming HTTP request
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        $this->requestStartTime = microtime(true);
        
        try {
            $this->addCorrelationId($request);
            $response = $this->sendRequestThroughRouter($request);
        } catch (Throwable $e) {
            $response = $this->exceptionHandler->handle($request, $e);
        }
        
        return $this->addTimingHeaders($response);
    }

    /**
     * Perform any final actions for the request lifecycle
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void
    {
        $this->logRequest($request, $response);
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function setRouteMiddleware(array $middleware): static
    {
        $this->routeMiddleware = $middleware;
        return $this;
    }

    public function setMiddlewareGroups(array $groups): static
    {
        $this->middlewareGroups = $groups;
        return $this;
    }

    public function setExceptionHandler(ExceptionHandler $handler): static
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * Send request through global middleware and router
     */
    protected function sendRequestThroughRouter(RequestInterface $request): ResponseInterface
    {
        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->gatherGlobalMiddleware())
            ->then(function (RequestInterface $request) {
                $response = $this->router->dispatch($request);
                
                $route = $this->router->getCurrentRoute();
                if ($route !== null) {
                    $routeMiddleware = $this->gatherRouteMiddleware($route->getMiddleware());
                    if (!empty($routeMiddleware)) {
                        return (new Pipeline($this->app))
                            ->send($request)
                            ->through($routeMiddleware)
                            ->then(fn() => $response);
                    }
                }
                
                return $response;
            });
    }

    protected function gatherGlobalMiddleware(): array
    {
        return array_map(fn(string $m) => $this->resolveMiddleware($m), $this->middleware);
    }

    protected function gatherRouteMiddleware(array $middleware): array
    {
        $resolved = [];
        
        foreach ($middleware as $name) {
            if (isset($this->middlewareGroups[$name])) {
                $resolved = array_merge($resolved, $this->middlewareGroups[$name]);
            } elseif (isset($this->routeMiddleware[$name])) {
                $resolved[] = $this->routeMiddleware[$name];
            } else {
                $resolved[] = $name;
            }
        }
        
        return array_map(fn(string $m) => $this->resolveMiddleware($m), $resolved);
    }

    protected function resolveMiddleware(string $middleware): string
    {
        if (str_contains($middleware, ':')) {
            return $middleware;
        }
        return $this->routeMiddleware[$middleware] ?? $middleware;
    }

    protected function addCorrelationId(RequestInterface $request): void
    {
        $correlationId = $request->header('X-Correlation-ID') 
            ?? $request->header('X-Request-ID')
            ?? bin2hex(random_bytes(8));
        
        if ($this->app->has('logger')) {
            $logger = $this->app->make('logger');
            if (method_exists($logger, 'setCorrelationId')) {
                $logger->setCorrelationId($correlationId);
            }
        }
    }

    protected function addTimingHeaders(ResponseInterface $response): ResponseInterface
    {
        $duration = (microtime(true) - $this->requestStartTime) * 1000;
        
        return $response
            ->header('X-Response-Time', sprintf('%.2fms', $duration))
            ->header('X-Memory-Usage', sprintf('%.2fMB', memory_get_peak_usage(true) / 1024 / 1024));
    }

    protected function logRequest(RequestInterface $request, ResponseInterface $response): void
    {
        $duration = (microtime(true) - $this->requestStartTime) * 1000;
        
        logger()->info('Request completed', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }
}
