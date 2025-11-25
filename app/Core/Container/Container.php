<?php

declare(strict_types=1);

namespace App\Core\Container;

use App\Core\Contracts\Container\ContainerInterface;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * IoC Container
 * 
 * Provides dependency injection and service location
 * with automatic constructor injection via reflection
 */
class Container implements ContainerInterface
{
    /**
     * The container's bindings
     *
     * @var array<string, array{concrete: Closure|string, singleton: bool}>
     */
    protected array $bindings = [];

    /**
     * The container's singleton instances
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * The registered type aliases
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * The types that are currently being resolved
     *
     * @var array<string, true>
     */
    protected array $buildStack = [];

    /**
     * The types that have been resolved
     *
     * @var array<string, true>
     */
    protected array $resolved = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $singleton = false): void
    {
        // Remove existing instance if rebinding
        unset($this->instances[$abstract], $this->resolved[$abstract]);

        // If no concrete type given, assume the abstract is concrete
        $concrete = $concrete ?? $abstract;

        // Wrap non-closure concretes in a closure
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, singleton: true);
    }

    /**
     * {@inheritdoc}
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        // Remove existing binding
        unset($this->bindings[$abstract]);

        // Store the instance
        $this->instances[$abstract] = $instance;
        $this->resolved[$abstract] = true;

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new \LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * {@inheritdoc}
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) 
            || isset($this->instances[$abstract])
            || isset($this->aliases[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolved(string $abstract): bool
    {
        return isset($this->resolved[$abstract]) 
            || isset($this->instances[$abstract]);
    }

    /**
     * Resolve the given type from the container
     *
     * @param string $abstract The abstract type to resolve
     * @param array $parameters Optional parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // If we have an instance, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete type
        $concrete = $this->getConcrete($abstract);

        // If the concrete type is the same as abstract and is buildable, build it
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            // Otherwise, resolve the concrete type
            $object = $this->make($concrete, $parameters);
        }

        // If this was a singleton binding, store the instance
        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $object;
        }

        // Mark as resolved
        $this->resolved[$abstract] = true;

        return $object;
    }

    /**
     * Get the concrete type for a given abstract
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract): mixed
    {
        // If we have a binding, return its concrete
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        // Otherwise, return the abstract (we'll try to build it)
        return $abstract;
    }

    /**
     * Determine if the given concrete is buildable
     *
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type
     *
     * @param Closure|string $concrete
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function build(Closure|string $concrete, array $parameters = []): mixed
    {
        // If it's a Closure, invoke it with the container
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw BindingResolutionException::unresolvable($concrete, $e->getMessage());
        }

        // Check if the class is instantiable
        if (!$reflector->isInstantiable()) {
            throw BindingResolutionException::uninstantiable($concrete, "Class is not instantiable");
        }

        // Check for circular dependencies
        if (isset($this->buildStack[$concrete])) {
            throw BindingResolutionException::circularDependency($concrete);
        }

        // Add to build stack
        $this->buildStack[$concrete] = true;

        try {
            $constructor = $reflector->getConstructor();

            // If there's no constructor, instantiate directly
            if ($constructor === null) {
                unset($this->buildStack[$concrete]);
                return new $concrete();
            }

            // Resolve constructor dependencies
            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters
            );

            unset($this->buildStack[$concrete]);

            return $reflector->newInstanceArgs($dependencies);
            
        } catch (\Exception $e) {
            unset($this->buildStack[$concrete]);
            throw BindingResolutionException::unresolvable($concrete, $e->getMessage());
        }
    }

    /**
     * Resolve all dependencies for the given parameters
     *
     * @param ReflectionParameter[] $dependencies
     * @param array $parameters
     * @return array
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If we have a parameter for this dependency, use it
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            // Try to resolve from type hint
            $result = $this->resolveDependency($dependency);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Resolve a single dependency
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // If no type hint, check for default value
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw BindingResolutionException::unresolvable(
                $parameter->getName(),
                "Cannot resolve primitive parameter [{$parameter->getName()}] without a default value"
            );
        }

        // Try to resolve the class dependency
        try {
            return $this->make($type->getName());
        } catch (BindingResolutionException $e) {
            // If it's optional or nullable, return null
            if ($parameter->isOptional() || $type->allowsNull()) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * Get the closure to wrap non-closure concretes
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return fn(Container $container, array $parameters = []) 
            => $container->build($concrete, $parameters);
    }

    /**
     * Get the alias for an abstract if available
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Determine if a given type is bound as a singleton
     *
     * @param string $abstract
     * @return bool
     */
    protected function isSingleton(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton'] === true);
    }

    /**
     * Flush the container of all bindings and resolved instances
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->resolved = [];
        $this->buildStack = [];
    }
}
