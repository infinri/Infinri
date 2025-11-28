<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Application;
use App\Core\Http\ExceptionHandler;
use App\Core\Http\Request;
use App\Core\Http\JsonResponse;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $handler;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/exception_handler_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=Test\nAPP_DEBUG=false\n");
        
        Application::resetInstance();
        $app = new Application($this->tempDir);
        $app->bootstrap();
        
        $this->handler = new ExceptionHandler(false);
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
    public function it_handles_route_not_found_html(): void
    {
        $request = Request::create('/missing', 'GET');
        $exception = new RouteNotFoundException('/missing', 'GET');
        
        $response = $this->handler->handleNotFound($request, $exception);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Not Found', $response->getContent());
    }

    #[Test]
    public function it_handles_route_not_found_json(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/missing',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new RouteNotFoundException('/missing', 'GET');
        
        $response = $this->handler->handleNotFound($request, $exception);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function it_handles_method_not_allowed_html(): void
    {
        $request = Request::create('/users', 'DELETE');
        $exception = new MethodNotAllowedException('/users', 'DELETE', ['GET', 'POST']);
        
        $response = $this->handler->handleMethodNotAllowed($request, $exception);
        
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('GET, POST', $response->getHeader('allow'));
    }

    #[Test]
    public function it_handles_method_not_allowed_json(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/users',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new MethodNotAllowedException('/users', 'DELETE', ['GET', 'POST']);
        
        $response = $this->handler->handleMethodNotAllowed($request, $exception);
        
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['GET', 'POST'], $data['allowed_methods']);
    }

    #[Test]
    public function it_handles_generic_exception_html(): void
    {
        $request = Request::create('/error', 'GET');
        $exception = new \RuntimeException('Something went wrong');
        
        $response = $this->handler->handleGeneric($request, $exception);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', $response->getContent());
    }

    #[Test]
    public function it_handles_generic_exception_json(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/error',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new \RuntimeException('Something went wrong');
        
        $response = $this->handler->handleGeneric($request, $exception);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[Test]
    public function it_shows_debug_info_when_enabled(): void
    {
        $handler = new ExceptionHandler(true);
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/error',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new \RuntimeException('Debug message');
        
        $response = $handler->handleGeneric($request, $exception);
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('Debug message', $data['message']);
        $this->assertArrayHasKey('exception', $data);
        $this->assertArrayHasKey('file', $data);
        $this->assertArrayHasKey('line', $data);
    }

    #[Test]
    public function it_hides_debug_info_when_disabled(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/error',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new \RuntimeException('Secret error');
        
        $response = $this->handler->handleGeneric($request, $exception);
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('An unexpected error occurred.', $data['message']);
        $this->assertArrayNotHasKey('exception', $data);
    }

    #[Test]
    public function it_shows_debug_html_when_enabled(): void
    {
        $handler = new ExceptionHandler(true);
        $request = Request::create('/error', 'GET');
        $exception = new \RuntimeException('Debug message');
        
        $response = $handler->handleGeneric($request, $exception);
        
        $this->assertStringContainsString('Debug message', $response->getContent());
        $this->assertStringContainsString('<pre>', $response->getContent());
    }

    #[Test]
    public function it_can_set_debug_mode(): void
    {
        $this->handler->setDebug(true);
        
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/error',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new \RuntimeException('Now visible');
        
        $response = $this->handler->handleGeneric($request, $exception);
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('Now visible', $data['message']);
    }

    #[Test]
    public function it_dispatches_to_correct_handler(): void
    {
        $request = Request::create('/test', 'GET');
        
        $notFound = new RouteNotFoundException('/test', 'GET');
        $response1 = $this->handler->handle($request, $notFound);
        $this->assertEquals(404, $response1->getStatusCode());
        
        $methodNotAllowed = new MethodNotAllowedException('/test', 'POST', ['GET']);
        $response2 = $this->handler->handle($request, $methodNotAllowed);
        $this->assertEquals(405, $response2->getStatusCode());
        
        $generic = new \Exception('Error');
        $response3 = $this->handler->handle($request, $generic);
        $this->assertEquals(500, $response3->getStatusCode());
    }

}
