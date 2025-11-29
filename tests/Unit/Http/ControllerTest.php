<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Core\Application;
use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/controller_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=Test\n");
        
        Application::resetInstance();
        $app = new Application($this->tempDir);
        $app->bootstrap();
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
    public function it_creates_json_response(): void
    {
        $controller = new TestController();
        
        $response = $controller->testJson(['foo' => 'bar']);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
    }

    #[Test]
    public function it_creates_success_response(): void
    {
        $controller = new TestController();
        
        $response = $controller->testSuccess(['id' => 1], 'Created');
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Created', $data['message']);
    }

    #[Test]
    public function it_creates_error_response(): void
    {
        $controller = new TestController();
        
        $response = $controller->testError('Validation failed', 422, ['name' => 'Required']);
        
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    #[Test]
    public function it_creates_standard_response(): void
    {
        $controller = new TestController();
        
        $response = $controller->testResponse('Hello World', 201);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Hello World', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function it_creates_redirect_response(): void
    {
        $controller = new TestController();
        
        $response = $controller->testRedirect('/dashboard');
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dashboard', $response->getTargetUrl());
    }

    #[Test]
    public function it_can_make_services(): void
    {
        $controller = new TestController();
        
        $result = $controller->testMake('config');
        
        $this->assertNotNull($result);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $controller = new TestController();
        
        $request = Request::create('/test', 'POST', ['name' => 'John']);
        $validated = $controller->testValidate($request, ['name' => 'required']);
        
        $this->assertEquals(['name' => 'John'], $validated);
    }

    #[Test]
    public function it_throws_on_missing_required_field(): void
    {
        $controller = new TestController();
        
        $request = Request::create('/test', 'POST', []);
        
        $this->expectException(\App\Core\Validation\ValidationException::class);
        $this->expectExceptionMessage('Name is required');
        
        $controller->testValidate($request, ['name' => 'required']);
    }
}

// Test controller that exposes protected methods
class TestController extends Controller
{
    public function testJson(mixed $data): JsonResponse
    {
        return $this->json($data);
    }

    public function testSuccess(mixed $data, string $message): JsonResponse
    {
        return $this->success($data, $message);
    }

    public function testError(string $message, int $status, array $errors): JsonResponse
    {
        return $this->error($message, $status, $errors);
    }

    public function testResponse(string $content, int $status): Response
    {
        return $this->response($content, $status);
    }

    public function testRedirect(string $url): RedirectResponse
    {
        return $this->redirect($url);
    }

    public function testMake(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    public function testValidate(Request $request, array $rules): array
    {
        return $this->validate($request, $rules);
    }
}
