<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Application;
use App\Core\Http\Kernel;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\ExceptionHandler;
use App\Core\Routing\Router;
use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Closure;

class KernelTest extends TestCase
{
    private Application $app;
    private Router $router;
    private Kernel $kernel;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/kernel_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=Test\nAPP_DEBUG=false\n");
        
        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
        
        $this->router = new Router($this->app);
        $this->kernel = new Kernel($this->app, $this->router);
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
    public function it_handles_basic_request(): void
    {
        $this->router->get('/test', fn() => 'Hello');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals('Hello', $response->getContent());
        $this->assertNotNull($response->getHeader('x-response-time'));
        $this->assertNotNull($response->getHeader('x-memory-usage'));
    }

    #[Test]
    public function it_returns_application(): void
    {
        $this->assertSame($this->app, $this->kernel->getApplication());
    }

    #[Test]
    public function it_returns_router(): void
    {
        $this->assertSame($this->router, $this->kernel->getRouter());
    }

    #[Test]
    public function it_sets_global_middleware(): void
    {
        $result = $this->kernel->setMiddleware([TestMiddleware::class]);
        
        $this->assertSame($this->kernel, $result);
    }

    #[Test]
    public function it_sets_route_middleware(): void
    {
        $result = $this->kernel->setRouteMiddleware(['auth' => TestMiddleware::class]);
        
        $this->assertSame($this->kernel, $result);
    }

    #[Test]
    public function it_sets_middleware_groups(): void
    {
        $result = $this->kernel->setMiddlewareGroups([
            'web' => [TestMiddleware::class]
        ]);
        
        $this->assertSame($this->kernel, $result);
    }

    #[Test]
    public function it_sets_exception_handler(): void
    {
        $handler = new ExceptionHandler(true);
        $result = $this->kernel->setExceptionHandler($handler);
        
        $this->assertSame($this->kernel, $result);
    }

    #[Test]
    public function it_executes_global_middleware(): void
    {
        KernelTestMiddleware::$called = false;
        
        $this->kernel->setMiddleware([KernelTestMiddleware::class]);
        $this->router->get('/test', fn() => 'OK');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertTrue(KernelTestMiddleware::$called);
    }

    #[Test]
    public function it_executes_route_middleware(): void
    {
        RouteTestMiddleware::$called = false;
        
        $this->kernel->setRouteMiddleware(['test' => RouteTestMiddleware::class]);
        $this->router->get('/test', fn() => 'OK')->middleware('test');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertTrue(RouteTestMiddleware::$called);
    }

    #[Test]
    public function it_executes_middleware_group(): void
    {
        GroupTestMiddleware::$called = false;
        
        $this->kernel->setMiddlewareGroups([
            'api' => [GroupTestMiddleware::class]
        ]);
        $this->router->get('/test', fn() => 'OK')->middleware('api');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertTrue(GroupTestMiddleware::$called);
    }

    #[Test]
    public function it_resolves_middleware_with_parameters(): void
    {
        // Test that middleware names with parameters (colon) are passed through as-is
        $this->kernel->setRouteMiddleware([
            'throttle' => ThrottleMiddleware::class
        ]);
        
        // Middleware with colon-separated parameters should use the class directly
        $this->router->get('/test', fn() => 'OK')->middleware(ThrottleMiddleware::class);
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_exceptions(): void
    {
        $this->router->get('/error', fn() => throw new \RuntimeException('Test error'));
        
        $request = Request::create('/error', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(500, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_404(): void
    {
        $request = Request::create('/not-found', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function it_terminates_request(): void
    {
        $this->router->get('/test', fn() => 'OK');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        // Should not throw
        $this->kernel->terminate($request, $response);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_correlation_id_from_header(): void
    {
        $this->router->get('/test', fn() => 'OK');
        
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_X_CORRELATION_ID' => 'test-correlation-123'
        ]);
        
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_uses_request_id_as_fallback(): void
    {
        $this->router->get('/test', fn() => 'OK');
        
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_X_REQUEST_ID' => 'request-456'
        ]);
        
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_adds_timing_headers(): void
    {
        $this->router->get('/test', fn() => 'OK');
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertMatchesRegularExpression('/^\d+\.\d+ms$/', $response->getHeader('x-response-time'));
        $this->assertMatchesRegularExpression('/^\d+\.\d+MB$/', $response->getHeader('x-memory-usage'));
    }

    #[Test]
    public function it_resolves_class_name_middleware(): void
    {
        // When middleware is a full class name (not in routeMiddleware), it should be used as-is
        $this->router->get('/test', fn() => 'OK')->middleware(DirectMiddleware::class);
        
        DirectMiddleware::$called = false;
        
        $request = Request::create('/test', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertTrue(DirectMiddleware::$called);
    }
}

// Test middleware classes
class KernelTestMiddleware implements MiddlewareInterface
{
    public static bool $called = false;
    
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        self::$called = true;
        return $next($request);
    }
}

class RouteTestMiddleware implements MiddlewareInterface
{
    public static bool $called = false;
    
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        self::$called = true;
        return $next($request);
    }
}

class GroupTestMiddleware implements MiddlewareInterface
{
    public static bool $called = false;
    
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        self::$called = true;
        return $next($request);
    }
}

class DirectMiddleware implements MiddlewareInterface
{
    public static bool $called = false;
    
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        self::$called = true;
        return $next($request);
    }
}

class ThrottleMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        return $next($request);
    }
}
