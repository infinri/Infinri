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
namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Contracts\Cache\CacheInterface;
use App\Core\Http\Response;

/**
 * Response Cache Middleware
 * 
 * Caches full HTTP responses for improved performance on
 * cacheable endpoints. Only caches GET requests and non-authenticated responses.
 */
class ResponseCacheMiddleware implements MiddlewareInterface
{
    /**
     * Cache instance
     */
    protected CacheInterface $cache;

    /**
     * Cache configuration
     */
    protected array $config;

    /**
     * Default configuration
     */
    protected array $defaults = [
        'enabled' => true,
        'ttl' => 300,                    // 5 minutes default
        'prefix' => 'response_cache:',
        'excluded_paths' => [            // Paths to never cache
            '/api/*',
            '/admin/*',
            '/_*',
        ],
        'excluded_params' => [           // Query params that prevent caching
            'nocache',
            'preview',
        ],
        'vary_by' => [                   // Headers to vary cache by
            'Accept-Encoding',
            'Accept-Language',
        ],
        'cacheable_status_codes' => [    // Only cache these status codes
            200, 301, 302,
        ],
    ];

    public function __construct(CacheInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = array_merge($this->defaults, $config);
    }

    /**
     * Handle the request
     */
    public function handle(RequestInterface $request, \Closure $next): ResponseInterface
    {
        // Skip if caching is disabled
        if (!$this->config['enabled']) {
            return $next($request);
        }

        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $next($request);
        }

        // Skip if request has no-cache headers
        if ($this->hasNoCacheHeaders($request)) {
            return $next($request);
        }

        // Skip excluded paths
        if ($this->isExcludedPath($request->getPath())) {
            return $next($request);
        }

        // Skip if has excluded query params
        if ($this->hasExcludedParams($request)) {
            return $next($request);
        }

        // Skip if user is authenticated (has session)
        if ($this->isAuthenticated($request)) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);

        // Try to get cached response
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $this->createCachedResponse($cached);
        }

        // Execute the request
        $response = $next($request);

        // Cache the response if appropriate
        if ($this->shouldCache($response)) {
            $this->cacheResponse($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Check if request has no-cache headers
     */
    protected function hasNoCacheHeaders(RequestInterface $request): bool
    {
        $cacheControl = $request->getHeader('Cache-Control');

        if ($cacheControl) {
            $directives = array_map('trim', explode(',', $cacheControl));
            if (in_array('no-cache', $directives) || in_array('no-store', $directives)) {
                return true;
            }
        }

        return $request->getHeader('Pragma') === 'no-cache';
    }

    /**
     * Check if path is excluded from caching
     */
    protected function isExcludedPath(string $path): bool
    {
        foreach ($this->config['excluded_paths'] as $pattern) {
            if ($this->pathMatches($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if path matches a pattern
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Convert glob pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['[^/]*', '\/'],
            $pattern
        );

        return (bool) preg_match('/^' . $regex . '$/', $path);
    }

    /**
     * Check if request has excluded query params
     */
    protected function hasExcludedParams(RequestInterface $request): bool
    {
        $params = $request->getQueryParams();

        foreach ($this->config['excluded_params'] as $param) {
            if (array_key_exists($param, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(RequestInterface $request): bool
    {
        // Check for session cookie or authorization header
        $cookies = $request->getCookies();
        
        // Check for PHP session cookie
        if (isset($cookies[session_name()])) {
            return true;
        }

        // Check for authorization header
        if ($request->getHeader('Authorization')) {
            return true;
        }

        return false;
    }

    /**
     * Generate cache key for the request
     */
    protected function generateCacheKey(RequestInterface $request): string
    {
        $parts = [
            $request->getMethod(),
            $request->getPath(),
        ];

        // Include sorted query string
        $query = $request->getQueryParams();
        if (!empty($query)) {
            ksort($query);
            $parts[] = http_build_query($query);
        }

        // Include vary-by headers
        foreach ($this->config['vary_by'] as $header) {
            $value = $request->getHeader($header);
            if ($value) {
                $parts[] = $header . ':' . $value;
            }
        }

        return $this->config['prefix'] . md5(implode('|', $parts));
    }

    /**
     * Check if response should be cached
     */
    protected function shouldCache(ResponseInterface $response): bool
    {
        // Check status code
        if (!in_array($response->getStatusCode(), $this->config['cacheable_status_codes'])) {
            return false;
        }

        // Check response cache-control headers
        $cacheControl = $response->getHeader('Cache-Control');
        if ($cacheControl) {
            $directives = array_map('trim', explode(',', $cacheControl));
            if (in_array('private', $directives) || in_array('no-store', $directives)) {
                return false;
            }
        }

        // Check for Set-Cookie header (indicates session/personalization)
        if ($response->getHeader('Set-Cookie')) {
            return false;
        }

        return true;
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(string $key, ResponseInterface $response): void
    {
        $cached = [
            'status' => $response->getStatusCode(),
            'headers' => $this->filterHeaders($response->getHeaders()),
            'body' => $response->getBody(),
            'cached_at' => time(),
        ];

        // Determine TTL from response headers or use default
        $ttl = $this->determineTtl($response);

        $this->cache->put($key, $cached, $ttl);
    }

    /**
     * Filter headers for caching (remove hop-by-hop headers)
     */
    protected function filterHeaders(array $headers): array
    {
        $hopByHop = [
            'Connection', 'Keep-Alive', 'Proxy-Authenticate',
            'Proxy-Authorization', 'TE', 'Trailers',
            'Transfer-Encoding', 'Upgrade', 'Set-Cookie',
        ];

        return array_filter(
            $headers,
            fn($name) => !in_array($name, $hopByHop, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Determine TTL from response headers
     */
    protected function determineTtl(ResponseInterface $response): int
    {
        $cacheControl = $response->getHeader('Cache-Control');

        if ($cacheControl) {
            // Look for max-age directive
            if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
                return (int) $matches[1];
            }

            // Look for s-maxage (shared cache max age)
            if (preg_match('/s-maxage=(\d+)/', $cacheControl, $matches)) {
                return (int) $matches[1];
            }
        }

        // Check Expires header
        $expires = $response->getHeader('Expires');
        if ($expires) {
            $expiresTime = strtotime($expires);
            if ($expiresTime !== false && $expiresTime > time()) {
                return $expiresTime - time();
            }
        }

        return $this->config['ttl'];
    }

    /**
     * Create a response from cached data
     */
    protected function createCachedResponse(array $cached): ResponseInterface
    {
        $response = new Response(
            $cached['body'],
            $cached['status'],
            $cached['headers']
        );

        // Add cache hit header
        $response = $response->withHeader('X-Cache', 'HIT');
        $response = $response->withHeader('X-Cache-Age', (string) (time() - $cached['cached_at']));

        return $response;
    }

    /**
     * Clear cached response for a specific path
     */
    public function invalidate(string $path): bool
    {
        // Generate a pattern to match
        $pattern = $this->config['prefix'] . '*';

        // Note: Full invalidation by path requires cache to support pattern deletion
        // For Redis, this would use KEYS or SCAN
        if (method_exists($this->cache, 'clearByPattern')) {
            return $this->cache->clearByPattern($pattern) > 0;
        }

        return false;
    }

    /**
     * Clear all cached responses
     */
    public function flush(): bool
    {
        if (method_exists($this->cache, 'clearByPattern')) {
            return $this->cache->clearByPattern($this->config['prefix'] . '*') > 0;
        }

        return false;
    }
}
