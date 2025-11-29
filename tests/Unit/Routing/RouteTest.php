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
namespace Tests\Unit\Routing;

use App\Core\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    #[Test]
    public function it_can_be_created(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        
        $this->assertInstanceOf(Route::class, $route);
    }

    #[Test]
    public function it_returns_uri(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        
        $this->assertEquals('/users', $route->getUri());
    }

    #[Test]
    public function it_normalizes_uri(): void
    {
        $route = new Route(['GET'], 'users/', fn() => 'Users');
        
        $this->assertEquals('/users', $route->getUri());
    }

    #[Test]
    public function it_returns_methods(): void
    {
        $route = new Route(['GET', 'POST'], '/users', fn() => 'Users');
        
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
    }

    #[Test]
    public function it_uppercases_methods(): void
    {
        $route = new Route(['get', 'post'], '/users', fn() => 'Users');
        
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
    }

    #[Test]
    public function it_returns_action(): void
    {
        $action = fn() => 'Users';
        $route = new Route(['GET'], '/users', $action);
        
        $this->assertSame($action, $route->getAction());
    }

    #[Test]
    public function it_can_set_name(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->name('users.index');
        
        $this->assertEquals('users.index', $route->getName());
    }

    #[Test]
    public function it_returns_null_name_by_default(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        
        $this->assertNull($route->getName());
    }

    #[Test]
    public function it_can_add_middleware(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->middleware('auth');
        
        $this->assertContains('auth', $route->getMiddleware());
    }

    #[Test]
    public function it_can_add_multiple_middleware(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->middleware(['auth', 'verified']);
        
        $this->assertContains('auth', $route->getMiddleware());
        $this->assertContains('verified', $route->getMiddleware());
    }

    #[Test]
    public function it_can_chain_middleware(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->middleware('auth')->middleware('throttle');
        
        $this->assertContains('auth', $route->getMiddleware());
        $this->assertContains('throttle', $route->getMiddleware());
    }

    #[Test]
    public function it_can_add_where_constraint(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => 'User');
        $route->where('id', '[0-9]+');
        
        $this->assertEquals(['id' => '[0-9]+'], $route->getWheres());
    }

    #[Test]
    public function it_can_add_multiple_where_constraints(): void
    {
        $route = new Route(['GET'], '/posts/{category}/{id}', fn() => 'Post');
        $route->where(['category' => '[a-z]+', 'id' => '[0-9]+']);
        
        $wheres = $route->getWheres();
        $this->assertEquals('[a-z]+', $wheres['category']);
        $this->assertEquals('[0-9]+', $wheres['id']);
    }

    #[Test]
    public function it_matches_simple_path(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        
        $this->assertTrue($route->matches('/users', 'GET'));
        $this->assertFalse($route->matches('/posts', 'GET'));
    }

    #[Test]
    public function it_matches_method(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        
        $this->assertTrue($route->matches('/users', 'GET'));
        $this->assertFalse($route->matches('/users', 'POST'));
    }

    #[Test]
    public function it_matches_path_with_parameter(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => 'User');
        
        $this->assertTrue($route->matches('/users/123', 'GET'));
        $this->assertTrue($route->matches('/users/abc', 'GET'));
        $this->assertFalse($route->matches('/users', 'GET'));
    }

    #[Test]
    public function it_extracts_parameters(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => 'User');
        $route->matches('/users/123', 'GET');
        
        $this->assertEquals(['id' => '123'], $route->getParameters());
    }

    #[Test]
    public function it_extracts_multiple_parameters(): void
    {
        $route = new Route(['GET'], '/posts/{category}/{slug}', fn() => 'Post');
        $route->matches('/posts/tech/hello-world', 'GET');
        
        $params = $route->getParameters();
        $this->assertEquals('tech', $params['category']);
        $this->assertEquals('hello-world', $params['slug']);
    }

    #[Test]
    public function it_matches_optional_parameter_when_present(): void
    {
        $route = new Route(['GET'], '/page/{name?}', fn() => 'Page');
        
        $this->assertTrue($route->matches('/page/about', 'GET'));
        
        $params = $route->getParameters();
        $this->assertEquals('about', $params['name']);
    }

    #[Test]
    public function it_matches_optional_parameter_when_absent(): void
    {
        $route = new Route(['GET'], '/page/{name?}', fn() => 'Page');
        
        $this->assertTrue($route->matches('/page', 'GET'));
        
        $params = $route->getParameters();
        $this->assertArrayNotHasKey('name', $params);
    }

    #[Test]
    public function it_applies_where_constraint(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => 'User');
        $route->where('id', '[0-9]+');
        
        $this->assertTrue($route->matches('/users/123', 'GET'));
        $this->assertFalse($route->matches('/users/abc', 'GET'));
    }

    #[Test]
    public function it_returns_compiled_pattern(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => 'User');
        
        $pattern = $route->getCompiledPattern();
        
        $this->assertStringStartsWith('#^', $pattern);
        $this->assertStringEndsWith('$#u', $pattern);
        $this->assertStringContainsString('(?P<id>', $pattern);
    }

    #[Test]
    public function it_returns_parameter_names(): void
    {
        $route = new Route(['GET'], '/posts/{category}/{slug}', fn() => 'Post');
        
        $names = $route->getParameterNames();
        
        $this->assertContains('category', $names);
        $this->assertContains('slug', $names);
    }

    #[Test]
    public function it_can_set_prefix(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->setPrefix('/api/v1');
        
        $this->assertEquals('/api/v1/users', $route->getUri());
    }

    #[Test]
    public function it_can_set_name_prefix(): void
    {
        $route = new Route(['GET'], '/users', fn() => 'Users');
        $route->name('index');
        $route->setNamePrefix('users.');
        
        $this->assertEquals('users.index', $route->getName());
    }

    #[Test]
    public function it_handles_root_path(): void
    {
        $route = new Route(['GET'], '/', fn() => 'Home');
        
        $this->assertTrue($route->matches('/', 'GET'));
        $this->assertEquals('/', $route->getUri());
    }

    #[Test]
    public function it_handles_optional_parameter_with_prefix_not_ending_in_slash(): void
    {
        // URI pattern where optional param comes after text without trailing slash
        $route = new Route(['GET'], '/file{ext?}', fn() => 'File');
        
        $this->assertTrue($route->matches('/file', 'GET'));
        $this->assertTrue($route->matches('/file.txt', 'GET'));
    }

    #[Test]
    public function it_handles_optional_parameter_with_slash_prefix(): void
    {
        $route = new Route(['GET'], '/files/{name?}', fn() => 'Files');
        
        $this->assertTrue($route->matches('/files', 'GET'));
        $this->assertTrue($route->matches('/files/', 'GET'));
        $this->assertTrue($route->matches('/files/document', 'GET'));
    }
}
