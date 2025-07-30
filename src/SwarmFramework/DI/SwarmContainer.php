<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\DI;

use Infinri\SwarmFramework\Interfaces\SwarmUnitInterface;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionParameter;
use ReflectionException;

/**
 * SwarmContainer - Conscious Dependency Resolution
 * 
 * PSR-11 compliant dependency injection container with consciousness-level awareness.
 * Provides specialized resolution for SwarmUnits with capability injection and validation.
 * 
 * @performance O(1) resolution via cached reflection
 * @architecture PSR-11 compliant with SwarmUnit awareness
 * @reference infinri_blueprint.md → FR-CORE-019 (Dependency Injection)
 * @author Infinri Framework
 * @version 1.0.0
 */
final class SwarmContainer implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $reflectionCache = [];
    private array $consciousnessServices = [];

    /**
     * Initialize container with consciousness-level services
     */
    public function __construct()
    {
        $this->registerConsciousnessServices();
    }

    /**
     * Retrieve an entry from the container
     * 
     * @param string $id Entry identifier
     * @return mixed Entry
     * @throws NotFoundExceptionInterface If entry not found
     * @throws ContainerExceptionInterface If entry cannot be resolved
     */
    public function get(string $id): mixed
    {
        // Return singleton instance if exists
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Check if binding exists
        if (!$this->has($id)) {
            throw new ContainerNotFoundException("Entry '{$id}' not found in container");
        }

        // Resolve the entry
        return $this->resolve($id);
    }

    /**
     * Check if container can return an entry for the given identifier
     * 
     * @param string $id Entry identifier
     * @return bool True if entry exists
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || 
               isset($this->instances[$id]) || 
               class_exists($id) || 
               interface_exists($id);
    }

    /**
     * Bind a service to the container
     * 
     * @param string $abstract Service abstract/interface
     * @param mixed $concrete Service concrete implementation or factory
     * @param bool $singleton Whether to treat as singleton
     * @return void
     */
    public function bind(string $abstract, mixed $concrete = null, bool $singleton = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => $singleton,
            'resolved' => false
        ];
    }

    /**
     * Bind a singleton service to the container
     * 
     * @param string $abstract Service abstract/interface
     * @param mixed $concrete Service concrete implementation or factory
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve dependencies with consciousness-level awareness
     * 
     * @param string $class Class to resolve
     * @param array $parameters Additional parameters
     * @return object Resolved instance
     * @throws ContainerExceptionInterface If resolution fails
     */
    public function resolve(string $class, array $parameters = []): object
    {
        // Check if we have a binding for this identifier first
        if (isset($this->bindings[$class])) {
            $concrete = $this->bindings[$class]['concrete'];
            $isSingleton = $this->bindings[$class]['singleton'];
            
            // If singleton and already instantiated, return existing instance
            if ($isSingleton && isset($this->instances[$class])) {
                return $this->instances[$class];
            }
            
            // If concrete is a callable, invoke it
            if (is_callable($concrete)) {
                $instance = $concrete($this, $parameters);
            } else {
                // Resolve the concrete class
                $instance = $this->resolve($concrete, $parameters);
            }
            
            // Store singleton instance
            if ($isSingleton) {
                $this->instances[$class] = $instance;
            }
            
            return $instance;
        }
        
        // If no binding exists, try to auto-resolve the class
        try {
            $reflection = $this->getReflection($class);
            
            // Special handling for SwarmUnits
            if ($reflection->implementsInterface(SwarmUnitInterface::class)) {
                return $this->resolveSwarmUnit($reflection, $parameters);
            }
            
            return $this->resolveStandardClass($reflection, $parameters);
            
        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot resolve class '{$class}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * SwarmUnit-specific resolution with capability injection
     * 
     * @param ReflectionClass $reflection Class reflection
     * @param array $parameters Additional parameters
     * @return SwarmUnitInterface Resolved SwarmUnit
     */
    private function resolveSwarmUnit(ReflectionClass $reflection, array $parameters): SwarmUnitInterface
    {
        $instance = $this->instantiateWithDependencies($reflection, $parameters);
        
        // Inject consciousness-level services
        $this->injectConsciousnessServices($instance);
        
        // Validate unit capabilities against dependencies
        $this->validateUnitCapabilities($instance);
        
        // Store as singleton if marked
        $className = $reflection->getName();
        if (isset($this->bindings[$className]) && $this->bindings[$className]['singleton']) {
            $this->instances[$className] = $instance;
        }
        
        return $instance;
    }

    /**
     * Resolve standard class with dependency injection
     * 
     * @param ReflectionClass $reflection Class reflection
     * @param array $parameters Additional parameters
     * @return object Resolved instance
     */
    private function resolveStandardClass(ReflectionClass $reflection, array $parameters): object
    {
        $instance = $this->instantiateWithDependencies($reflection, $parameters);
        
        // Store as singleton if marked
        $className = $reflection->getName();
        if (isset($this->bindings[$className]) && $this->bindings[$className]['singleton']) {
            $this->instances[$className] = $instance;
        }
        
        return $instance;
    }

    /**
     * Instantiate class with dependency injection
     * 
     * @param ReflectionClass $reflection Class reflection
     * @param array $parameters Additional parameters
     * @return object Class instance
     */
    private function instantiateWithDependencies(ReflectionClass $reflection, array $parameters): object
    {
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return $reflection->newInstance();
        }
        
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);
        
        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies
     * 
     * @param array $parameters Constructor parameters
     * @param array $primitives Primitive values
     * @return array Resolved dependencies
     */
    private function resolveDependencies(array $parameters, array $primitives): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter, $primitives);
            $dependencies[] = $dependency;
        }
        
        return $dependencies;
    }

    /**
     * Resolve single dependency
     * 
     * @param ReflectionParameter $parameter Parameter reflection
     * @param array $primitives Primitive values
     * @return mixed Resolved dependency
     */
    private function resolveDependency(ReflectionParameter $parameter, array $primitives): mixed
    {
        $name = $parameter->getName();
        
        // Check if primitive value provided
        if (array_key_exists($name, $primitives)) {
            return $primitives[$name];
        }
        
        $type = $parameter->getType();
        
        if (!$type || $type->isBuiltin()) {
            // Handle primitive types
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new ContainerException("Cannot resolve primitive parameter '{$name}'");
        }
        
        $className = $type->getName();
        
        // Check for Injectable attribute
        $injectableValue = $this->getInjectableValue($parameter);
        if ($injectableValue !== null) {
            return $this->get($injectableValue);
        }
        
        return $this->get($className);
    }

    /**
     * Get Injectable attribute value
     * 
     * @param ReflectionParameter $parameter Parameter reflection
     * @return string|null Injectable value or null
     */
    private function getInjectableValue(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(Injectable::class);
        
        if (empty($attributes)) {
            return null;
        }
        
        $injectable = $attributes[0]->newInstance();
        return $injectable->value ?? null;
    }

    /**
     * Inject consciousness-level services into all SwarmUnits
     * 
     * @param SwarmUnitInterface $unit SwarmUnit instance
     * @return void
     */
    private function injectConsciousnessServices(SwarmUnitInterface $unit): void
    {
        foreach ($this->consciousnessServices as $service => $instance) {
            $setterMethod = 'set' . ucfirst($service);
            
            if (method_exists($unit, $setterMethod)) {
                $unit->$setterMethod($instance);
            }
        }
    }

    /**
     * Validate unit capabilities against dependencies
     * 
     * @param SwarmUnitInterface $unit SwarmUnit instance
     * @return void
     * @throws ContainerException If validation fails
     */
    private function validateUnitCapabilities(SwarmUnitInterface $unit): void
    {
        $identity = $unit->getIdentity();
        $requiredCapabilities = $this->extractRequiredCapabilities($unit);
        $unitCapabilities = $identity->capabilities ?? [];
        
        $missingCapabilities = array_diff($requiredCapabilities, $unitCapabilities);
        
        if (!empty($missingCapabilities)) {
            throw new ContainerException(
                "Unit '{$identity->id}' missing required capabilities: " . 
                implode(', ', $missingCapabilities)
            );
        }
    }

    /**
     * Extract required capabilities from unit
     * 
     * @param SwarmUnitInterface $unit SwarmUnit instance
     * @return array Required capabilities
     */
    private function extractRequiredCapabilities(SwarmUnitInterface $unit): array
    {
        $reflection = new ReflectionClass($unit);
        $attributes = $reflection->getAttributes(Injectable::class);
        
        if (empty($attributes)) {
            return [];
        }
        
        $injectable = $attributes[0]->newInstance();
        return $injectable->requiredCapabilities ?? [];
    }

    /**
     * Get reflection for class with caching
     * 
     * @param string $class Class name
     * @return ReflectionClass Class reflection
     */
    private function getReflection(string $class): ReflectionClass
    {
        if (!isset($this->reflectionCache[$class])) {
            $this->reflectionCache[$class] = new ReflectionClass($class);
        }
        
        return $this->reflectionCache[$class];
    }

    /**
     * Register consciousness-level services
     * 
     * @return void
     */
    private function registerConsciousnessServices(): void
    {
        // These would be injected into all SwarmUnits
        $this->consciousnessServices = [
            'entropyMonitor' => null, // Will be resolved when needed
            'ethicalValidator' => null,
            'throttlingManager' => null,
            'temporalEngine' => null,
            'aclManager' => null,
            'tracer' => null
        ];
    }

    /**
     * Get all registered bindings
     * 
     * @return array All bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all singleton instances
     * 
     * @return array All instances
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * Clear all instances (useful for testing)
     * 
     * @return void
     */
    public function clearInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Flush the container (clear everything)
     * 
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->reflectionCache = [];
        $this->registerConsciousnessServices();
    }
}

/**
 * Container Exception - Dependency resolution failure
 */
final class ContainerException extends \Exception implements ContainerExceptionInterface
{
}

/**
 * Container Not Found Exception - Entry not found
 */
final class ContainerNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
