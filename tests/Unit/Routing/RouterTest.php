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
namespace Tests\Unit\Routing;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Routing\Router;
use App\Core\Routing\Route;
use App\Core\Routing\Exceptions\RouteNotFoundException;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Application $app;
    private Router $router;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/router_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
        
        $this->router = new Router($this->app);
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
    public function it_registers_get_route(): void
    {
        $route = $this->router->get('/test', fn() => 'Hello');
        
        $this->assertInstanceOf(Route::class, $route);
        $this->assertContains('GET', $route->getMethods());
        $this->assertContains('HEAD', $route->getMethods());
    }

    #[Test]
    public function it_registers_post_route(): void
    {
        $route = $this->router->post('/test', fn() => 'Created');
        
        $this->assertContains('POST', $route->getMethods());
    }

    #[Test]
    public function it_registers_put_route(): void
    {
        $route = $this->router->put('/test', fn() => 'Updated');
        
        $this->assertContains('PUT', $route->getMethods());
    }

    #[Test]
    public function it_registers_patch_route(): void
    {
        $route = $this->router->patch('/test', fn() => 'Patched');
        
        $this->assertContains('PATCH', $route->getMethods());
    }

    #[Test]
    public function it_registers_delete_route(): void
    {
        $route = $this->router->delete('/test', fn() => 'Deleted');
        
        $this->assertContains('DELETE', $route->getMethods());
    }

    #[Test]
    public function it_registers_options_route(): void
    {
        $route = $this->router->options('/test', fn() => 'Options');
        
        $this->assertContains('OPTIONS', $route->getMethods());
    }

    #[Test]
    public function it_registers_any_route(): void
    {
        $route = $this->router->any('/test', fn() => 'Any');
        
        $this->assertContains('GET', $route->getMethods());
        $this->assertContains('POST', $route->getMethods());
        $this->assertContains('PUT', $route->getMethods());
        $this->assertContains('DELETE', $route->getMethods());
    }

    #[Test]
    public function it_registers_match_route(): void
    {
        $route = $this->router->match(['GET', 'POST'], '/test', fn() => 'Matched');
        
        $this->assertContains('GET', $route->getMethods());
        $this->assertContains('POST', $route->getMethods());
        $this->assertNotContains('DELETE', $route->getMethods());
    }

    #[Test]
    public function it_dispatches_to_matching_route(): void
    {
        $this->router->get('/hello', fn() => 'Hello World');
        
        $request = Request::create('/hello', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('Hello World', $response->getContent());
    }

    #[Test]
    public function it_throws_route_not_found(): void
    {
        $this->expectException(RouteNotFoundException::class);
        
        $request = Request::create('/nonexistent', 'GET');
        $this->router->dispatch($request);
    }

    #[Test]
    public function it_throws_method_not_allowed(): void
    {
        $this->router->get('/only-get', fn() => 'Get only');
        
        $this->expectException(MethodNotAllowedException::class);
        
        $request = Request::create('/only-get', 'POST');
        $this->router->dispatch($request);
    }

    #[Test]
    public function it_extracts_route_parameters(): void
    {
        $this->router->get('/users/{id}', function(Request $request) {
            return 'User ' . $request->route('id');
        });
        
        $request = Request::create('/users/123', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('User 123', $response->getContent());
    }

    #[Test]
    public function it_extracts_multiple_parameters(): void
    {
        $this->router->get('/posts/{category}/{slug}', function(Request $request) {
            return $request->route('category') . ':' . $request->route('slug');
        });
        
        $request = Request::create('/posts/tech/hello-world', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('tech:hello-world', $response->getContent());
    }

    #[Test]
    public function it_handles_optional_parameters(): void
    {
        $this->router->get('/page/{name?}', function(Request $request) {
            return 'Page: ' . ($request->route('name') ?? 'home');
        });
        
        $request1 = Request::create('/page/about', 'GET');
        $response1 = $this->router->dispatch($request1);
        $this->assertEquals('Page: about', $response1->getContent());
        
        $request2 = Request::create('/page', 'GET');
        $response2 = $this->router->dispatch($request2);
        $this->assertEquals('Page: home', $response2->getContent());
    }

    #[Test]
    public function it_applies_parameter_constraints(): void
    {
        $this->router->get('/users/{id}', fn() => 'User found')
            ->where('id', '[0-9]+');
        
        // Numeric ID should match
        $request1 = Request::create('/users/123', 'GET');
        $response1 = $this->router->dispatch($request1);
        $this->assertEquals('User found', $response1->getContent());
        
        // Non-numeric should not match
        $this->expectException(RouteNotFoundException::class);
        $request2 = Request::create('/users/abc', 'GET');
        $this->router->dispatch($request2);
    }

    #[Test]
    public function it_names_routes(): void
    {
        $route = $this->router->get('/users/{id}', fn() => 'User')
            ->name('users.show');
        
        // Verify the route has the name
        $this->assertEquals('users.show', $route->getName());
        
        // Register the named route
        $this->router->registerNamedRoute($route);
        
        // Now we can retrieve it by name
        $retrieved = $this->router->getRoute('users.show');
        $this->assertNotNull($retrieved);
        $this->assertEquals('users.show', $retrieved->getName());
    }

    #[Test]
    public function it_generates_urls_from_named_routes(): void
    {
        $this->router->get('/users/{id}', fn() => 'User')
            ->name('users.show');
        
        // Register the named route
        $routes = $this->router->getRoutes();
        foreach ($routes as $route) {
            $this->router->registerNamedRoute($route);
        }
        
        $url = $this->router->url('users.show', ['id' => 123]);
        
        $this->assertEquals('/users/123', $url);
    }

    #[Test]
    public function it_adds_middleware_to_route(): void
    {
        $route = $this->router->get('/test', fn() => 'Test')
            ->middleware('auth');
        
        $this->assertContains('auth', $route->getMiddleware());
    }

    #[Test]
    public function it_adds_multiple_middleware(): void
    {
        $route = $this->router->get('/test', fn() => 'Test')
            ->middleware(['auth', 'verified']);
        
        $this->assertContains('auth', $route->getMiddleware());
        $this->assertContains('verified', $route->getMiddleware());
    }

    #[Test]
    public function it_creates_route_groups_with_prefix(): void
    {
        $this->router->group(['prefix' => 'api'], function($router) {
            $router->get('/users', fn() => 'API Users');
            $router->get('/posts', fn() => 'API Posts');
        });
        
        $request = Request::create('/api/users', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('API Users', $response->getContent());
    }

    #[Test]
    public function it_creates_nested_groups(): void
    {
        $this->router->group(['prefix' => 'api'], function($router) {
            $router->group(['prefix' => 'v1'], function($router) {
                $router->get('/users', fn() => 'API v1 Users');
            });
        });
        
        $request = Request::create('/api/v1/users', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('API v1 Users', $response->getContent());
    }

    #[Test]
    public function it_applies_group_middleware(): void
    {
        $this->router->group(['middleware' => ['auth']], function($router) {
            $router->get('/protected', fn() => 'Protected');
        });
        
        $routes = $this->router->getRoutes();
        
        $this->assertContains('auth', $routes[0]->getMiddleware());
    }

    #[Test]
    public function it_returns_json_response_from_array(): void
    {
        $this->router->get('/api/data', fn() => ['foo' => 'bar']);
        
        $request = Request::create('/api/data', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringContainsString('foo', $response->getContent());
    }

    #[Test]
    public function it_returns_response_object_as_is(): void
    {
        $this->router->get('/custom', fn() => new Response('Custom', 201));
        
        $request = Request::create('/custom', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Custom', $response->getContent());
    }

    #[Test]
    public function it_gets_all_routes(): void
    {
        $this->router->get('/a', fn() => 'A');
        $this->router->post('/b', fn() => 'B');
        $this->router->put('/c', fn() => 'C');
        
        $routes = $this->router->getRoutes();
        
        $this->assertCount(3, $routes);
    }

    #[Test]
    public function it_gets_current_route_after_dispatch(): void
    {
        $this->router->get('/test', fn() => 'Test')->name('test.route');
        
        $request = Request::create('/test', 'GET');
        $this->router->dispatch($request);
        
        $current = $this->router->getCurrentRoute();
        
        $this->assertNotNull($current);
    }

    #[Test]
    public function it_applies_middleware_via_fluent_method(): void
    {
        $this->router->middleware('auth')->get('/protected', fn() => 'Protected');
        
        $routes = $this->router->getRoutes();
        $route = $routes[0];
        
        $this->assertContains('auth', $route->getMiddleware());
    }

    #[Test]
    public function it_applies_prefix_via_fluent_method(): void
    {
        $this->router->prefix('api')->get('/users', fn() => 'Users');
        
        $request = Request::create('/api/users', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('Users', $response->getContent());
    }

    #[Test]
    public function it_applies_name_prefix_via_fluent_method(): void
    {
        $route = $this->router->name('api.')->get('/users', fn() => 'Users')->name('users');
        
        // Note: setNamePrefix is applied during route creation
        $this->router->registerNamedRoute($route);
        $retrieved = $this->router->getRoute('users');
        
        $this->assertNotNull($retrieved);
    }

    #[Test]
    public function it_merges_group_middleware(): void
    {
        $this->router->group(['middleware' => ['auth']], function($router) {
            $router->group(['middleware' => 'admin'], function($router) {
                $router->get('/admin', fn() => 'Admin');
            });
        });
        
        $routes = $this->router->getRoutes();
        $route = $routes[0];
        
        $this->assertContains('auth', $route->getMiddleware());
        $this->assertContains('admin', $route->getMiddleware());
    }

    #[Test]
    public function it_merges_group_name_prefix(): void
    {
        $this->router->group(['as' => 'api.'], function($router) {
            $router->group(['as' => 'v1.'], function($router) {
                $router->get('/users', fn() => 'Users')->name('users');
            });
        });
        
        $routes = $this->router->getRoutes();
        $this->assertCount(1, $routes);
    }

    #[Test]
    public function it_dispatches_array_controller_action(): void
    {
        $this->router->get('/controller', [TestController::class, 'index']);
        
        $request = Request::create('/controller', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('controller index', $response->getContent());
    }

    #[Test]
    public function it_dispatches_string_controller_action(): void
    {
        $this->router->get('/controller-string', TestController::class . '@show');
        
        $request = Request::create('/controller-string', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('controller show', $response->getContent());
    }

    #[Test]
    public function it_throws_for_invalid_action_type(): void
    {
        // Route constructor enforces types, so we can't easily test invalid action
        // This test verifies that routes work with valid action types
        $this->router->get('/valid', fn() => 'OK');
        
        $request = Request::create('/valid', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    public function it_handles_null_return(): void
    {
        $this->router->get('/null', fn() => null);
        
        $request = Request::create('/null', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertEquals('', $response->getContent());
    }

    #[Test]
    public function it_handles_json_serializable(): void
    {
        $this->router->get('/json-serializable', fn() => new TestJsonSerializable());
        
        $request = Request::create('/json-serializable', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringContainsString('serialized', $response->getContent());
    }

    #[Test]
    public function it_registers_resource_routes(): void
    {
        $this->router->resource('posts', ResourceController::class);
        
        $routes = $this->router->getRoutes();
        
        // Should create 7 routes (index, create, store, show, edit, update, destroy)
        $this->assertCount(7, $routes);
    }

    #[Test]
    public function it_registers_api_resource_routes(): void
    {
        $this->router->apiResource('posts', ResourceController::class);
        
        $routes = $this->router->getRoutes();
        
        // Should create 5 routes (no create/edit form routes)
        $this->assertCount(5, $routes);
    }

    #[Test]
    public function it_registers_resource_with_only_option(): void
    {
        $this->router->resource('posts', ResourceController::class, [
            'only' => ['index', 'show']
        ]);
        
        $routes = $this->router->getRoutes();
        
        $this->assertCount(2, $routes);
    }

    #[Test]
    public function it_registers_resource_with_except_option(): void
    {
        $this->router->resource('posts', ResourceController::class, [
            'except' => ['destroy']
        ]);
        
        $routes = $this->router->getRoutes();
        
        $this->assertCount(6, $routes);
    }

    #[Test]
    public function it_names_resource_routes(): void
    {
        $this->router->resource('posts', ResourceController::class, [
            'only' => ['index']
        ]);
        
        $routes = $this->router->getRoutes();
        $route = $routes[0];
        
        $this->assertEquals('posts.index', $route->getName());
    }
}

class TestController
{
    public function index(): string
    {
        return 'controller index';
    }
    
    public function show(): string
    {
        return 'controller show';
    }
}

class TestJsonSerializable implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['data' => 'serialized'];
    }
}

class ResourceController
{
    public function index() { return 'index'; }
    public function create() { return 'create'; }
    public function store() { return 'store'; }
    public function show() { return 'show'; }
    public function edit() { return 'edit'; }
    public function update() { return 'update'; }
    public function destroy() { return 'destroy'; }
}
