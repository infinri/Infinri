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
namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

/**
 * Request Timing Middleware
 *
 * Logs request timing and adds timing headers for observability.
 * Required for Phase 2 observability requirements.
 */
class RequestTimingMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Execute the request
        $response = $next($request);

        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        // Log the request timing
        $this->logRequestTiming($request, $response, $duration, $memoryUsed);

        return $response;
    }

    protected function logRequestTiming(
        RequestInterface $request,
        ResponseInterface $response,
        float $durationMs,
        int $memoryBytes
    ): void {
        if (! function_exists('logger')) {
            return;
        }

        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
            'memory_bytes' => $memoryBytes,
            'memory_mb' => round($memoryBytes / 1024 / 1024, 2),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Log to info channel for normal requests
        if ($response->getStatusCode() < 400) {
            logger()->info('Request completed', $context);
        } elseif ($response->getStatusCode() < 500) {
            logger()->warning('Client error response', $context);
        } else {
            logger()->error('Server error response', $context);
        }
    }
}
