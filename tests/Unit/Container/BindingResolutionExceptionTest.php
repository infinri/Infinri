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
namespace Tests\Unit\Container;

use App\Core\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BindingResolutionExceptionTest extends TestCase
{
    #[Test]
    public function it_creates_exception_with_message(): void
    {
        $exception = new BindingResolutionException('Test message', 'TestClass');
        
        $this->assertSame('Test message', $exception->getMessage());
    }

    #[Test]
    public function it_returns_abstract(): void
    {
        $exception = new BindingResolutionException('Test message', 'TestClass');
        
        $this->assertSame('TestClass', $exception->getAbstract());
    }

    #[Test]
    public function it_returns_reason(): void
    {
        $exception = new BindingResolutionException('Test message', 'TestClass', 'Some reason');
        
        $this->assertSame('Some reason', $exception->getReason());
    }

    #[Test]
    public function reason_is_nullable(): void
    {
        $exception = new BindingResolutionException('Test message', 'TestClass');
        
        $this->assertNull($exception->getReason());
    }

    #[Test]
    public function unresolvable_creates_exception(): void
    {
        $exception = BindingResolutionException::unresolvable('UnresolvableClass');
        
        $this->assertInstanceOf(BindingResolutionException::class, $exception);
        $this->assertSame('UnresolvableClass', $exception->getAbstract());
        $this->assertStringContainsString('UnresolvableClass', $exception->getMessage());
    }

    #[Test]
    public function unresolvable_with_custom_message(): void
    {
        $exception = BindingResolutionException::unresolvable('TestClass', 'Custom message');
        
        $this->assertSame('Custom message', $exception->getMessage());
    }

    #[Test]
    public function circular_dependency_creates_exception(): void
    {
        $exception = BindingResolutionException::circularDependency('CircularClass');
        
        $this->assertInstanceOf(BindingResolutionException::class, $exception);
        $this->assertStringContainsString('Circular dependency', $exception->getMessage());
    }

    #[Test]
    public function uninstantiable_creates_exception(): void
    {
        $exception = BindingResolutionException::uninstantiable('AbstractClass', 'Cannot instantiate');
        
        $this->assertInstanceOf(BindingResolutionException::class, $exception);
        $this->assertStringContainsString('not instantiable', $exception->getMessage());
    }
}
