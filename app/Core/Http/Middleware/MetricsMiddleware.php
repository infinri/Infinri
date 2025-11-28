<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Metrics\MetricsCollector;

/**
 * Metrics Middleware
 * 
 * Automatically records request metrics (timing, status codes).
 * Add to global middleware stack for comprehensive metrics.
 */
class MetricsMiddleware
{
    protected MetricsCollector $collector;
    protected float $startTime;

    public function __construct(?MetricsCollector $collector = null)
    {
        $this->collector = $collector ?? new MetricsCollector();
    }

    /**
     * Handle the request
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $this->startTime = microtime(true);

        /** @var ResponseInterface $response */
        $response = $next($request);

        $this->recordMetrics($request, $response);

        return $response;
    }

    /**
     * Record request metrics
     */
    protected function recordMetrics(RequestInterface $request, ResponseInterface $response): void
    {
        $duration = microtime(true) - $this->startTime;
        $method = $request->getMethod();
        $path = $request->getPathInfo();
        $statusCode = $response->getStatusCode();

        $this->collector->recordRequest($method, $path, $statusCode, $duration);
    }
}
