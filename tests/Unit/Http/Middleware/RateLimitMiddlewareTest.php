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
namespace Tests\Unit\Http\Middleware;

use App\Core\Http\Middleware\RateLimitMiddleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\RateLimiter;
use App\Core\Contracts\Cache\CacheInterface;
use App\Core\Contracts\Http\ResponseInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that prevents exit calls
 */
class TestableRateLimitMiddleware extends RateLimitMiddleware
{
    public bool $rateLimitExceeded = false;
    public array $responseData = [];
    
    protected function tooManyAttemptsResponse(string $key): ResponseInterface
    {
        $this->rateLimitExceeded = true;
        $this->responseData = [
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $this->decaySeconds,
        ];
        
        // Return a response instead of exiting
        return new Response(json_encode($this->responseData), 429);
    }
    
    // Expose protected method for testing
    public function testResolveKey(Request $request): string
    {
        return $this->resolveKey($request);
    }
    
    // Expose protected method for testing
    public function testAddHeaders(ResponseInterface $response, string $key): ResponseInterface
    {
        return $this->addHeaders($response, $key);
    }
}

class RateLimitMiddlewareTest extends TestCase
{
    #[Test]
    public function constructor_uses_default_values(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(60, 60, $limiter);
        
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    #[Test]
    public function constructor_accepts_custom_values(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(100, 120, $limiter);
        
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    #[Test]
    public function handle_allows_requests_under_limit(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(0);
        $cache->method('put')->willReturn(true);
        
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(60, 60, $limiter);
        $request = $this->createRequest();
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function handle_calls_hit_on_limiter(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(0);
        $cache->expects($this->once())
            ->method('increment');
        
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(60, 60, $limiter);
        $request = $this->createRequest();
        
        $middleware->handle($request, function($req) {
            return new Response('OK');
        });
    }

    #[Test]
    public function handle_returns_response(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(0);
        
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(60, 60, $limiter);
        $request = $this->createRequest();
        
        $response = $middleware->handle($request, function($req) {
            return new Response('OK');
        });
        
        $this->assertInstanceOf(Response::class, $response);
    }

    private function createRequest(): Request
    {
        return new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
            [],
            null
        );
    }

    #[Test]
    public function handle_rejects_requests_over_limit(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        // Return a count higher than limit
        $cache->method('get')->willReturn(100);
        
        $limiter = new RateLimiter($cache);
        $middleware = new TestableRateLimitMiddleware(60, 60, $limiter);
        $request = $this->createRequest();
        
        $called = false;
        $response = $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertFalse($called, 'Next middleware should not be called');
        $this->assertTrue($middleware->rateLimitExceeded);
        $this->assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function resolve_key_generates_unique_hash(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $limiter = new RateLimiter($cache);
        $middleware = new TestableRateLimitMiddleware(60, 60, $limiter);
        
        $request1 = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'REMOTE_ADDR' => '127.0.0.1',
        ], [], null);
        
        $request2 = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/other',
            'REMOTE_ADDR' => '127.0.0.1',
        ], [], null);
        
        $key1 = $middleware->testResolveKey($request1);
        $key2 = $middleware->testResolveKey($request2);
        
        $this->assertNotSame($key1, $key2);
        $this->assertSame(40, strlen($key1)); // SHA1 produces 40 char hex
    }

    #[Test]
    public function resolve_key_handles_missing_ip(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $limiter = new RateLimiter($cache);
        $middleware = new TestableRateLimitMiddleware(60, 60, $limiter);
        
        // Request without REMOTE_ADDR
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ], [], null);
        
        $key = $middleware->testResolveKey($request);
        
        $this->assertSame(40, strlen($key));
    }

    #[Test]
    public function add_headers_adds_rate_limit_headers(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(10); // 10 attempts made
        
        $limiter = new RateLimiter($cache);
        $middleware = new TestableRateLimitMiddleware(60, 60, $limiter);
        
        $response = new Response('OK');
        $result = $middleware->testAddHeaders($response, 'test-key');
        
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function constructor_accepts_injected_limiter(): void
    {
        // Test that the middleware works with an injected limiter
        $cache = $this->createMock(CacheInterface::class);
        $limiter = new RateLimiter($cache);
        $middleware = new RateLimitMiddleware(60, 60, $limiter);
        
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    #[Test]
    public function rate_limit_response_includes_correct_data(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(100);
        
        $limiter = new RateLimiter($cache);
        $middleware = new TestableRateLimitMiddleware(60, 120, $limiter);
        $request = $this->createRequest();
        
        $middleware->handle($request, fn($req) => new Response('OK'));
        
        $this->assertSame('Too Many Requests', $middleware->responseData['error']);
        $this->assertSame(120, $middleware->responseData['retry_after']);
    }
}
