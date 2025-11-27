<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Http\Middleware\RequestTimingMiddleware;

use App\Core\Application;
use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Middleware\Pipeline;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/middleware_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_executes_middleware_pipeline(): void
    {
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([])
            ->then(fn() => new Response('Final'));
        
        $this->assertEquals('Final', $response->getContent());
    }

    #[Test]
    public function it_executes_middleware_in_order(): void
    {
        $order = [];
        
        $middleware1 = function(RequestInterface $request, Closure $next) use (&$order) {
            $order[] = 'before1';
            $response = $next($request);
            $order[] = 'after1';
            return $response;
        };
        
        $middleware2 = function(RequestInterface $request, Closure $next) use (&$order) {
            $order[] = 'before2';
            $response = $next($request);
            $order[] = 'after2';
            return $response;
        };
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $pipeline
            ->send($request)
            ->through([$middleware1, $middleware2])
            ->then(function() use (&$order) {
                $order[] = 'handler';
                return new Response('Done');
            });
        
        $this->assertEquals(['before1', 'before2', 'handler', 'after2', 'after1'], $order);
    }

    #[Test]
    public function it_can_modify_request(): void
    {
        $middleware = function(RequestInterface $request, Closure $next) {
            // Add custom attribute via route parameters
            $request->setRouteParameters(['modified' => true]);
            return $next($request);
        };
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->then(function(RequestInterface $request) {
                return new Response($request->route('modified') ? 'Modified' : 'Not Modified');
            });
        
        $this->assertEquals('Modified', $response->getContent());
    }

    #[Test]
    public function it_can_modify_response(): void
    {
        $middleware = function(RequestInterface $request, Closure $next) {
            $response = $next($request);
            $response->header('X-Modified', 'true');
            return $response;
        };
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->then(fn() => new Response('Original'));
        
        $this->assertEquals('true', $response->getHeader('x-modified'));
    }

    #[Test]
    public function it_can_short_circuit(): void
    {
        $reachedHandler = false;
        
        $middleware = function(RequestInterface $request, Closure $next) {
            // Don't call $next, short circuit
            return new Response('Blocked', 403);
        };
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->then(function() use (&$reachedHandler) {
                $reachedHandler = true;
                return new Response('Handler');
            });
        
        $this->assertFalse($reachedHandler);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Blocked', $response->getContent());
    }

    #[Test]
    public function it_resolves_middleware_from_container(): void
    {
        // Register a middleware class
        $this->app->bind(TestMiddleware::class, TestMiddleware::class);
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([TestMiddleware::class])
            ->then(fn() => new Response('Handler'));
        
        $this->assertEquals('from-middleware', $response->getHeader('x-test'));
    }

    #[Test]
    public function it_handles_middleware_with_parameters(): void
    {
        $this->app->bind(ParameterizedMiddleware::class, ParameterizedMiddleware::class);
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([ParameterizedMiddleware::class . ':param1,param2'])
            ->then(fn() => new Response('Handler'));
        
        $this->assertEquals('param1,param2', $response->getHeader('x-params'));
    }

    #[Test]
    public function it_can_use_custom_method(): void
    {
        $middleware = new class {
            public function process(RequestInterface $request, Closure $next): ResponseInterface
            {
                $response = $next($request);
                $response->header('X-Custom-Method', 'used');
                return $response;
            }
        };
        
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->via('process')
            ->then(fn() => new Response('Handler'));
        
        $this->assertEquals('used', $response->getHeader('x-custom-method'));
    }

    #[Test]
    public function it_returns_response_from_then_return(): void
    {
        $pipeline = new Pipeline($this->app);
        $request = Request::create('/test', 'GET');
        
        $middleware = function(RequestInterface $request, Closure $next) {
            return new Response('Middleware Response');
        };
        
        $response = $pipeline
            ->send($request)
            ->through([$middleware])
            ->thenReturn();
        
        $this->assertEquals('Middleware Response', $response->getContent());
    }

    #[Test]
    public function it_runs_request_timing_middleware(): void
    {
        $middleware = new RequestTimingMiddleware();
        $request = Request::create('/test', 'GET');
        
        $response = $middleware->handle($request, function($req) {
            return new Response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $response = $next($request);
        $response->header('X-Test', 'from-middleware');
        return $response;
    }
}

class ParameterizedMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next, string ...$params): ResponseInterface
    {
        $response = $next($request);
        $response->header('X-Params', implode(',', $params));
        return $response;
    }
}
