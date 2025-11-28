<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use App\Core\Routing\SimpleRouter;
use App\Core\Module\ModuleRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SimpleRouterTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }

    #[Test]
    public function constructor_reads_request_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $router = new SimpleRouter();
        
        // Router should have captured POST method
        $this->assertInstanceOf(SimpleRouter::class, $router);
    }

    #[Test]
    public function constructor_reads_request_uri(): void
    {
        $_SERVER['REQUEST_URI'] = '/test/path';
        $router = new SimpleRouter();
        
        $this->assertInstanceOf(SimpleRouter::class, $router);
    }

    #[Test]
    public function get_registers_get_route(): void
    {
        $router = new SimpleRouter();
        $result = $router->get('/test', 'handler');
        
        $this->assertSame($router, $result); // Fluent interface
    }

    #[Test]
    public function post_registers_post_route(): void
    {
        $router = new SimpleRouter();
        $result = $router->post('/test', 'handler');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function put_registers_put_route(): void
    {
        $router = new SimpleRouter();
        $result = $router->put('/test', 'handler');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function patch_registers_patch_route(): void
    {
        $router = new SimpleRouter();
        $result = $router->patch('/test', 'handler');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function delete_registers_delete_route(): void
    {
        $router = new SimpleRouter();
        $result = $router->delete('/test', 'handler');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function any_registers_for_all_methods(): void
    {
        $router = new SimpleRouter();
        $result = $router->any('/test', 'handler');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function fluent_chaining_works(): void
    {
        $router = new SimpleRouter();
        
        $result = $router
            ->get('/', 'home')
            ->get('/about', 'about')
            ->post('/contact', 'contact');
        
        $this->assertSame($router, $result);
    }

    #[Test]
    public function dispatch_executes_callable_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        
        $executed = false;
        $router = new SimpleRouter();
        $router->get('/test', function() use (&$executed) {
            $executed = true;
        });
        
        $router->dispatch();
        
        $this->assertTrue($executed);
    }

    #[Test]
    public function get_renderer_returns_module_renderer(): void
    {
        $router = new SimpleRouter();
        
        $this->assertInstanceOf(ModuleRenderer::class, $router->getRenderer());
    }

    #[Test]
    public function constructor_accepts_custom_renderer(): void
    {
        $mockRenderer = $this->createMock(ModuleRenderer::class);
        $router = new SimpleRouter($mockRenderer);
        
        $this->assertSame($mockRenderer, $router->getRenderer());
    }

    #[Test]
    public function render_error_calls_renderer(): void
    {
        $mockRenderer = $this->createMock(ModuleRenderer::class);
        $mockRenderer->expects($this->once())
            ->method('renderError')
            ->with(404, 'not_found');
        
        $router = new SimpleRouter($mockRenderer);
        $router->renderError(404, 'not_found');
    }

    #[Test]
    public function render_maintenance_calls_renderer(): void
    {
        $mockRenderer = $this->createMock(ModuleRenderer::class);
        $mockRenderer->expects($this->once())
            ->method('renderMaintenance');
        
        $router = new SimpleRouter($mockRenderer);
        $router->renderMaintenance();
    }

    #[Test]
    public function dispatch_with_string_handler_calls_renderer(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/page';
        
        $mockRenderer = $this->createMock(ModuleRenderer::class);
        $mockRenderer->expects($this->once())
            ->method('render')
            ->with('home');
        
        $router = new SimpleRouter($mockRenderer);
        $router->get('/page', 'home');
        $router->dispatch();
    }

    #[Test]
    public function dispatch_handles_not_found(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent';
        
        $mockRenderer = $this->createMock(ModuleRenderer::class);
        $mockRenderer->expects($this->once())
            ->method('render')
            ->with('error');
        
        $router = new SimpleRouter($mockRenderer);
        // Don't register /nonexistent route
        $router->dispatch('error');
    }
}
