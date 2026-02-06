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
use App\Core\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    protected function tearDown(): void
    {
        $this->container->flush();
    }

    #[Test]
    public function it_can_bind_and_resolve_a_concrete_class(): void
    {
        $this->container->bind(ConcreteClass::class);
        
        $resolved = $this->container->make(ConcreteClass::class);
        
        $this->assertInstanceOf(ConcreteClass::class, $resolved);
    }

    #[Test]
    public function it_can_bind_interface_to_implementation(): void
    {
        $this->container->bind(ContractInterface::class, ConcreteImplementation::class);
        
        $resolved = $this->container->make(ContractInterface::class);
        
        $this->assertInstanceOf(ConcreteImplementation::class, $resolved);
    }

    #[Test]
    public function it_can_bind_using_closure(): void
    {
        $this->container->bind(ConcreteClass::class, function ($container) {
            return new ConcreteClass();
        });
        
        $resolved = $this->container->make(ConcreteClass::class);
        
        $this->assertInstanceOf(ConcreteClass::class, $resolved);
    }

    #[Test]
    public function it_resolves_new_instance_for_transient_bindings(): void
    {
        $this->container->bind(ConcreteClass::class);
        
        $first = $this->container->make(ConcreteClass::class);
        $second = $this->container->make(ConcreteClass::class);
        
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function it_resolves_same_instance_for_singleton_bindings(): void
    {
        $this->container->singleton(ConcreteClass::class);
        
        $first = $this->container->make(ConcreteClass::class);
        $second = $this->container->make(ConcreteClass::class);
        
        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_can_bind_instance(): void
    {
        $instance = new ConcreteClass();
        
        $this->container->instance(ConcreteClass::class, $instance);
        
        $resolved = $this->container->make(ConcreteClass::class);
        
        $this->assertSame($instance, $resolved);
    }

    #[Test]
    public function it_auto_resolves_constructor_dependencies(): void
    {
        $resolved = $this->container->make(ClassWithDependency::class);
        
        $this->assertInstanceOf(ClassWithDependency::class, $resolved);
        $this->assertInstanceOf(ConcreteClass::class, $resolved->dependency);
    }

    #[Test]
    public function it_resolves_nested_dependencies(): void
    {
        $resolved = $this->container->make(ClassWithNestedDependency::class);
        
        $this->assertInstanceOf(ClassWithNestedDependency::class, $resolved);
        $this->assertInstanceOf(ClassWithDependency::class, $resolved->dependency);
        $this->assertInstanceOf(ConcreteClass::class, $resolved->dependency->dependency);
    }

    #[Test]
    public function it_uses_bound_implementation_for_interface_dependency(): void
    {
        $this->container->bind(ContractInterface::class, ConcreteImplementation::class);
        
        $resolved = $this->container->make(ClassWithInterfaceDependency::class);
        
        $this->assertInstanceOf(ConcreteImplementation::class, $resolved->dependency);
    }

    #[Test]
    public function it_detects_circular_dependencies(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $this->container->make(CircularDependencyA::class);
    }

    #[Test]
    public function it_throws_exception_for_unresolvable_interface(): void
    {
        $this->expectException(BindingResolutionException::class);
        
        $this->container->make(ClassWithInterfaceDependency::class);
    }

    #[Test]
    public function it_throws_exception_for_unresolvable_primitive(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Cannot resolve primitive parameter');
        
        $this->container->make(ClassWithPrimitiveDependency::class);
    }

    #[Test]
    public function it_uses_default_value_for_optional_parameters(): void
    {
        $resolved = $this->container->make(ClassWithOptionalDependency::class);
        
        $this->assertNull($resolved->dependency);
    }

    #[Test]
    public function it_can_resolve_with_parameters(): void
    {
        $resolved = $this->container->make(ClassWithPrimitiveDependency::class, [
            'value' => 'test-value'
        ]);
        
        $this->assertEquals('test-value', $resolved->value);
    }

    #[Test]
    public function it_can_create_aliases(): void
    {
        $this->container->bind(ConcreteClass::class);
        $this->container->alias(ConcreteClass::class, 'concrete');
        
        $resolved = $this->container->make('concrete');
        
        $this->assertInstanceOf(ConcreteClass::class, $resolved);
    }

    #[Test]
    public function it_throws_exception_for_self_alias(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is aliased to itself');
        
        $this->container->alias('test', 'test');
    }

    #[Test]
    public function it_reports_bound_types(): void
    {
        $this->assertFalse($this->container->bound(ConcreteClass::class));
        
        $this->container->bind(ConcreteClass::class);
        
        $this->assertTrue($this->container->bound(ConcreteClass::class));
    }

    #[Test]
    public function it_reports_resolved_types(): void
    {
        $this->container->bind(ConcreteClass::class);
        
        $this->assertFalse($this->container->resolved(ConcreteClass::class));
        
        $this->container->make(ConcreteClass::class);
        
        $this->assertTrue($this->container->resolved(ConcreteClass::class));
    }

    #[Test]
    public function it_rebinds_and_clears_resolved_instances(): void
    {
        $this->container->singleton(ConcreteClass::class);
        $first = $this->container->make(ConcreteClass::class);
        
        $this->container->bind(ConcreteClass::class);
        $second = $this->container->make(ConcreteClass::class);
        
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function it_can_flush_all_bindings(): void
    {
        $this->container->bind(ConcreteClass::class);
        $this->container->singleton(ClassWithDependency::class);
        $this->container->alias(ConcreteClass::class, 'test');
        
        $this->container->flush();
        
        $this->assertFalse($this->container->bound(ConcreteClass::class));
        $this->assertFalse($this->container->bound(ClassWithDependency::class));
        $this->assertFalse($this->container->bound('test'));
    }

    #[Test]
    public function it_throws_exception_for_non_instantiable_class(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('is not instantiable');
        
        $this->container->make(AbstractClass::class);
    }

    #[Test]
    public function it_resolves_nullable_dependencies_as_null(): void
    {
        $resolved = $this->container->make(ClassWithNullableDependency::class);
        
        $this->assertNull($resolved->dependency);
    }

    #[Test]
    public function it_uses_default_value_for_primitive_with_class_dependency(): void
    {
        $resolved = $this->container->make(ClassWithMixedDependency::class);
        
        $this->assertInstanceOf(ConcreteClass::class, $resolved->concrete);
        $this->assertEquals('default', $resolved->value);
    }

    #[Test]
    public function it_throws_for_nonexistent_class(): void
    {
        $this->expectException(BindingResolutionException::class);
        
        $this->container->make('NonExistent\\Class\\That\\Does\\Not\\Exist');
    }

    #[Test]
    public function it_resolves_bound_interface_with_different_concrete(): void
    {
        // Bind interface to a concrete class (concrete != abstract)
        $this->container->bind(ContractInterface::class, ConcreteImplementation::class);
        
        $resolved = $this->container->make(ContractInterface::class);
        
        $this->assertInstanceOf(ConcreteImplementation::class, $resolved);
    }

    #[Test]
    public function it_resolves_string_binding_to_different_class(): void
    {
        // Bind a string abstract to a different concrete class (triggers line 166)
        $this->container->bind('my.service', ConcreteClass::class);
        
        $resolved = $this->container->make('my.service');
        
        $this->assertInstanceOf(ConcreteClass::class, $resolved);
    }

    #[Test]
    public function it_resolves_nested_interface_bindings(): void
    {
        // Bind ServiceA to interface, ServiceA depends on ConcreteClass
        $this->container->bind('service.wrapper', ServiceWrapper::class);
        
        $resolved = $this->container->make('service.wrapper');
        
        $this->assertInstanceOf(ServiceWrapper::class, $resolved);
        $this->assertInstanceOf(ConcreteClass::class, $resolved->dependency);
    }

    #[Test]
    public function it_calls_invokable_object(): void
    {
        $invokable = new InvokableClass();
        
        $result = $this->container->call($invokable);
        
        $this->assertSame('invoked', $result);
    }

    #[Test]
    public function it_calls_string_function(): void
    {
        // Test calling a global function by string name
        $result = $this->container->call('strlen', ['string' => 'hello']);
        
        $this->assertSame(5, $result);
    }

    #[Test]
    public function it_resolves_alias_to_concrete(): void
    {
        // Bind an alias that needs recursive resolution (line 168)
        $this->container->bind('my.alias', fn() => new ConcreteClass());
        $this->container->alias(ConcreteClass::class, 'concrete.alias');
        
        $result = $this->container->make('my.alias');
        
        $this->assertInstanceOf(ConcreteClass::class, $result);
    }

}

// Test fixtures

class InvokableClass
{
    public function __invoke(): string
    {
        return 'invoked';
    }
}

class ConcreteClass
{
}

interface ContractInterface
{
}

class ConcreteImplementation implements ContractInterface
{
}

class ClassWithDependency
{
    public function __construct(public ConcreteClass $dependency)
    {
    }
}

class ClassWithNestedDependency
{
    public function __construct(public ClassWithDependency $dependency)
    {
    }
}

class ClassWithInterfaceDependency
{
    public function __construct(public ContractInterface $dependency)
    {
    }
}

class CircularDependencyA
{
    public function __construct(public CircularDependencyB $dependency)
    {
    }
}

class CircularDependencyB
{
    public function __construct(public CircularDependencyA $dependency)
    {
    }
}

class ClassWithPrimitiveDependency
{
    public function __construct(public string $value)
    {
    }
}

class ClassWithOptionalDependency
{
    public function __construct(public ?ConcreteClass $dependency = null)
    {
    }
}

class ClassWithNullableDependency
{
    public function __construct(public ?ContractInterface $dependency)
    {
    }
}

abstract class AbstractClass
{
}

class ClassWithMixedDependency
{
    public function __construct(
        public ConcreteClass $concrete,
        public string $value = 'default'
    ) {
    }
}

class ServiceWrapper
{
    public function __construct(public ConcreteClass $dependency)
    {
    }
}
