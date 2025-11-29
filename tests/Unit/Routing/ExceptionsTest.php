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

use App\Core\Routing\Exceptions\RouteNotFoundException;
use App\Core\Routing\Exceptions\MethodNotAllowedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    #[Test]
    public function route_not_found_has_correct_message(): void
    {
        $exception = new RouteNotFoundException('/users/123', 'GET');
        
        $this->assertEquals('No route found for GET /users/123', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    #[Test]
    public function route_not_found_returns_path(): void
    {
        $exception = new RouteNotFoundException('/api/data', 'POST');
        
        $this->assertEquals('/api/data', $exception->getPath());
    }

    #[Test]
    public function route_not_found_returns_method(): void
    {
        $exception = new RouteNotFoundException('/test', 'DELETE');
        
        $this->assertEquals('DELETE', $exception->getMethod());
    }

    #[Test]
    public function method_not_allowed_has_correct_message(): void
    {
        $exception = new MethodNotAllowedException('/users', 'DELETE', ['GET', 'POST']);
        
        $this->assertEquals('Method DELETE not allowed for /users. Allowed: GET, POST', $exception->getMessage());
        $this->assertEquals(405, $exception->getCode());
    }

    #[Test]
    public function method_not_allowed_returns_path(): void
    {
        $exception = new MethodNotAllowedException('/api', 'PUT', ['GET']);
        
        $this->assertEquals('/api', $exception->getPath());
    }

    #[Test]
    public function method_not_allowed_returns_method(): void
    {
        $exception = new MethodNotAllowedException('/api', 'PATCH', ['GET']);
        
        $this->assertEquals('PATCH', $exception->getMethod());
    }

    #[Test]
    public function method_not_allowed_returns_allowed_methods(): void
    {
        $exception = new MethodNotAllowedException('/resource', 'DELETE', ['GET', 'POST', 'PUT']);
        
        $this->assertEquals(['GET', 'POST', 'PUT'], $exception->getAllowedMethods());
    }

    #[Test]
    public function method_not_allowed_handles_empty_allowed_methods(): void
    {
        $exception = new MethodNotAllowedException('/test', 'GET', []);
        
        $this->assertEquals([], $exception->getAllowedMethods());
        $this->assertStringContainsString('Allowed: ', $exception->getMessage());
    }
}
