<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Security\RateLimiter;
use App\Core\Cache\FileStore;

/**
 * Rate Limit Middleware
 * 
 * Thin wrapper around Core\Security\RateLimiter for middleware pipeline.
 */
class RateLimitMiddleware
{
    protected RateLimiter $limiter;
    protected int $maxAttempts;
    protected int $decaySeconds;

    public function __construct(
        int $maxAttempts = 60,
        int $decaySeconds = 60,
        ?RateLimiter $limiter = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->limiter = $limiter ?? $this->createDefaultLimiter();
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
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
        $ip = $request->getClientIp() ?? 'unknown';
        return sha1($ip . '|' . $request->getPathInfo());
    }

    protected function tooManyAttemptsResponse(string $key): ResponseInterface
    {
        $remaining = $this->limiter->retriesLeft($key, $this->maxAttempts);
        
        http_response_code(429);
        header('Content-Type: application/json');
        header("Retry-After: {$this->decaySeconds}");
        header("X-RateLimit-Limit: {$this->maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");

        echo json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $this->decaySeconds,
        ]);

        exit;
    }

    protected function addHeaders(ResponseInterface $response, string $key): ResponseInterface
    {
        $remaining = $this->limiter->retriesLeft($key, $this->maxAttempts);

        header("X-RateLimit-Limit: {$this->maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");

        return $response;
    }

    protected function createDefaultLimiter(): RateLimiter
    {
        $cachePath = $this->getCachePath();
        $cache = new FileStore($cachePath);
        return new RateLimiter($cache);
    }

    protected function getCachePath(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath('var/cache/rate_limits');
            } catch (\Throwable) {}
        }
        return dirname(__DIR__, 4) . '/var/cache/rate_limits';
    }
}
