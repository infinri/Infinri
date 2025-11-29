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
use App\Core\Routing\UrlGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new UrlGenerator('https://example.com');
    }

    #[Test]
    public function it_registers_named_route(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => null);
        $route->name('users.show');
        
        $this->generator->register($route);
        
        $this->assertTrue($this->generator->has('users.show'));
        $this->assertSame($route, $this->generator->getRoute('users.show'));
    }

    #[Test]
    public function it_generates_url_for_named_route(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => null);
        $route->name('users.show');
        $this->generator->register($route);
        
        $url = $this->generator->generate('users.show', ['id' => 42]);
        
        $this->assertEquals('/users/42', $url);
    }

    #[Test]
    public function it_generates_absolute_url(): void
    {
        $route = new Route(['GET'], '/users/{id}', fn() => null);
        $route->name('users.show');
        $this->generator->register($route);
        
        $url = $this->generator->generate('users.show', ['id' => 42], true);
        
        $this->assertEquals('https://example.com/users/42', $url);
    }

    #[Test]
    public function it_adds_query_parameters(): void
    {
        $route = new Route(['GET'], '/users', fn() => null);
        $route->name('users.index');
        $this->generator->register($route);
        
        $url = $this->generator->generate('users.index', ['page' => 2, 'sort' => 'name']);
        
        $this->assertStringContainsString('page=2', $url);
        $this->assertStringContainsString('sort=name', $url);
    }

    #[Test]
    public function it_handles_optional_parameters(): void
    {
        $route = new Route(['GET'], '/posts/{slug?}', fn() => null);
        $route->name('posts.show');
        $this->generator->register($route);
        
        $urlWithParam = $this->generator->generate('posts.show', ['slug' => 'hello']);
        $urlWithoutParam = $this->generator->generate('posts.show');
        
        $this->assertEquals('/posts/hello', $urlWithParam);
        // Note: trailing slash may be present when optional param is omitted
        $this->assertStringStartsWith('/posts', $urlWithoutParam);
    }

    #[Test]
    public function it_throws_for_undefined_route(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route [undefined] not defined.');
        
        $this->generator->generate('undefined');
    }

    #[Test]
    public function it_returns_null_for_missing_route(): void
    {
        $this->assertNull($this->generator->getRoute('missing'));
    }

    #[Test]
    public function it_checks_if_route_exists(): void
    {
        $route = new Route(['GET'], '/test', fn() => null);
        $route->name('test');
        $this->generator->register($route);
        
        $this->assertTrue($this->generator->has('test'));
        $this->assertFalse($this->generator->has('missing'));
    }

    #[Test]
    public function it_can_set_base_url(): void
    {
        $this->generator->setBaseUrl('https://api.example.com');
        
        $route = new Route(['GET'], '/users', fn() => null);
        $route->name('users');
        $this->generator->register($route);
        
        $url = $this->generator->generate('users', [], true);
        
        $this->assertEquals('https://api.example.com/users', $url);
    }

    #[Test]
    public function it_ignores_unnamed_routes(): void
    {
        $route = new Route(['GET'], '/test', fn() => null);
        $this->generator->register($route);
        
        $this->assertFalse($this->generator->has('test'));
    }
}
