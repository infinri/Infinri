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
namespace Tests\Unit\Http;

use App\Core\Application;
use App\Core\Http\Middleware\Pipeline;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Closure;

class PipelineTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pipeline_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=Test\n");
        
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
    public function it_runs_without_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([])
            ->then(fn($req) => new Response('Done'));
        
        $this->assertEquals('Done', $response->getContent());
    }

    #[Test]
    public function it_runs_closure_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        $middleware = function (RequestInterface $req, Closure $next): ResponseInterface {
            $response = $next($req);
            return $response->header('X-Closure', 'yes');
        };
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([$middleware])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('yes', $response->getHeader('x-closure'));
    }

    #[Test]
    public function it_runs_class_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([PipelineTestMiddleware::class])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('yes', $response->getHeader('x-pipeline-test'));
    }

    #[Test]
    public function it_runs_middleware_instance(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([new PipelineTestMiddleware()])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('yes', $response->getHeader('x-pipeline-test'));
    }

    #[Test]
    public function it_handles_middleware_with_parameters(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([ParameterMiddleware::class . ':foo,bar'])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('foo-bar', $response->getHeader('x-params'));
    }

    #[Test]
    public function it_chains_multiple_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        $first = function (RequestInterface $req, Closure $next): ResponseInterface {
            $response = $next($req);
            return $response->header('X-First', '1');
        };
        
        $second = function (RequestInterface $req, Closure $next): ResponseInterface {
            $response = $next($req);
            return $response->header('X-Second', '2');
        };
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([$first, $second])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('1', $response->getHeader('x-first'));
        $this->assertEquals('2', $response->getHeader('x-second'));
    }

    #[Test]
    public function it_can_use_custom_method(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([CustomMethodMiddleware::class])
            ->via('process')
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('custom', $response->getHeader('x-custom-method'));
    }

    #[Test]
    public function it_returns_empty_response_with_then_return(): void
    {
        $request = Request::create('/test', 'GET');
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([])
            ->thenReturn();
        
        $this->assertEquals('', $response->getContent());
    }

    #[Test]
    public function it_throws_for_invalid_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid middleware pipe type');
        
        (new Pipeline($this->app))
            ->send($request)
            ->through([123]) // Invalid type
            ->then(fn($req) => new Response('OK'));
    }

    #[Test]
    public function it_throws_for_missing_method(): void
    {
        $request = Request::create('/test', 'GET');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not have a nonexistent method');
        
        (new Pipeline($this->app))
            ->send($request)
            ->through([NoMethodMiddleware::class])
            ->via('nonexistent')
            ->then(fn($req) => new Response('OK'));
    }

    #[Test]
    public function it_handles_object_middleware(): void
    {
        $request = Request::create('/test', 'GET');
        
        // Plain object that implements handle
        $obj = new class {
            public function handle(RequestInterface $request, Closure $next): ResponseInterface
            {
                $response = $next($request);
                return $response->header('X-Object', 'works');
            }
        };
        
        $response = (new Pipeline($this->app))
            ->send($request)
            ->through([$obj])
            ->then(fn($req) => new Response('OK'));
        
        $this->assertEquals('works', $response->getHeader('x-object'));
    }
}

class PipelineTestMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $response = $next($request);
        return $response->header('X-Pipeline-Test', 'yes');
    }
}

class ParameterMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next, string $param1 = '', string $param2 = ''): ResponseInterface
    {
        $response = $next($request);
        return $response->header('X-Params', $param1 . '-' . $param2);
    }
}

class CustomMethodMiddleware
{
    public function process(RequestInterface $request, Closure $next): ResponseInterface
    {
        $response = $next($request);
        return $response->header('X-Custom-Method', 'custom');
    }
}

class NoMethodMiddleware
{
    // Intentionally has no handle or other method
}
