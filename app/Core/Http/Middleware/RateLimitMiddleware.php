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
use App\Core\Http\HttpStatus;
use App\Core\Http\JsonResponse;
use App\Core\Security\RateLimiter;
use Closure;

/**
 * Rate Limit Middleware
 *
 * Thin wrapper around Core\Security\RateLimiter for middleware pipeline.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    protected RateLimiter $limiter;

    public function __construct(
        protected int $maxAttempts = 60,
        protected int $decaySeconds = 60,
        ?RateLimiter $limiter = null
    ) {
        $this->limiter = $limiter ?? app(RateLimiter::class);
    }

    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $key = $this->resolveKey($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return $this->tooManyAttemptsResponse($key);
        }

        $this->limiter->hit($key, $this->decaySeconds);

        $response = $next($request);

        return $this->addHeaders($response, $key);
    }

    protected function resolveKey(RequestInterface $request): string
    {
        $ip = $request->ip() ?? 'unknown';

        return sha1($ip . '|' . $request->path());
    }

    protected function tooManyAttemptsResponse(string $key): ResponseInterface
    {
        $remaining = $this->limiter->retriesLeft($key, $this->maxAttempts);

        return new JsonResponse([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $this->decaySeconds,
        ], HttpStatus::TOO_MANY_REQUESTS)
            ->header('Retry-After', (string) $this->decaySeconds)
            ->header('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->header('X-RateLimit-Remaining', (string) $remaining);
    }

    protected function addHeaders(ResponseInterface $response, string $key): ResponseInterface
    {
        $remaining = $this->limiter->retriesLeft($key, $this->maxAttempts);

        return $response
            ->header('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->header('X-RateLimit-Remaining', (string) $remaining);
    }
}
