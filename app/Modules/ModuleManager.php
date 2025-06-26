<?php declare(strict_types=1);

namespace App\Modules;

use App\Modules\Discovery\ModuleDiscovery;
use App\Modules\Loader\ModuleLoader;
use App\Modules\Registry\ModuleRegistry;
use App\Modules\Dependency\DependencyResolver;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * ModuleManager is a facade for the module system
 * 
 * This class provides a simple interface for working with modules while
 * delegating the actual work to specialized components.
 */
class ModuleManager
{
    private ModuleRegistry $registry;
    private ModuleDiscovery $discovery;
    private ModuleLoader $loader;
    private DependencyResolver $resolver;
    
    /**
     * ModuleManager constructor
     */
    public function __construct(
        private ContainerInterface $container,
        private string $modulesPath,
        private bool $enableCache = true
    ) {
        $this->registry = new ModuleRegistry();
        $this->discovery = new ModuleDiscovery($modulesPath, $enableCache);
        $this->loader = new ModuleLoader($container, $this->registry);
        $this->resolver = new DependencyResolver($this->registry);
    }
    
    /**
     * Discover and register all available modules
     * 
     * @return array<class-string> Array of discovered module class names
     */
    public function discoverModules(): array
    {
        return $this->discovery->discover();
    }
    
    /**
     * Register a module by class name
     * 
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws RuntimeException If the module cannot be registered
     */
    public function registerModule(string $moduleClass): ModuleInterface
    {
        return $this->loader->load($moduleClass);
    }
    
    /**
     * Register multiple modules
     * 
     * @param array<class-string> $moduleClasses
     * @return array<ModuleInterface>
     */
    public function registerModules(array $moduleClasses): array
    {
        $modules = [];
        
        foreach ($moduleClasses as $moduleClass) {
            $modules[] = $this->registerModule($moduleClass);
        }
        
        return $modules;
    }
    
    /**
     * Resolve module dependencies and return modules in the correct order
     * 
     * @return array<ModuleInterface>
     */
    public function resolveDependencies(): array
    {
        $modules = $this->registry->all();
        
        if (empty($modules)) {
            return [];
        }
        
        // Create a temporary module for the event
        $tempModule = new class implements ModuleInterface {
            public function register(): void {}
            public function boot(): void {}
            public function getDependencies(): array { return []; }
            public function getId(): string { return 'dependency.resolver'; }
            public function getName(): string { return 'Dependency Resolver'; }
        };
        
        // Dispatch before resolution event
        $event = new ModuleDependencyEvent(
            $tempModule,
            array_keys($modules)
        );
        
        $this->dispatcher->dispatch($event);
        
        if ($event->isPropagationStopped()) {
            throw new RuntimeException(sprintf(
                'Dependency resolution was stopped by an event listener: %s',
                $event->getError()?->getMessage() ?? 'No reason provided'
            ));
        }
        
        try {
            $resolved = $this->resolver->resolveDependencies($modules);
            
            // Dispatch after successful resolution
            $this->dispatcher->dispatch(new ModuleDependencyEvent(
                $tempModule,
                array_keys($resolved)
            ));
            
            return $resolved;
        } catch (\Throwable $e) {
            // Dispatch resolution error event
            $this->dispatcher->dispatch(new ModuleDependencyEvent(
                $tempModule,
                array_keys($modules),
                [],
                [],
                $e
            ));
            
            throw $e;
        }
    }
    
    /**
     * Boot all registered modules
     */
    public function boot(): void
    {
        $modules = $this->registry->all();
        
        foreach ($modules as $module) {
            try {
                // Dispatch before boot event
                $event = new ModuleBootEvent($module, false, null, ['module' => $module]);
                $this->dispatcher->dispatch($event);
                
                if ($event->isPropagationStopped()) {
                    continue;
                }
                
                // Boot the module
                $module->boot();
                
                // Dispatch after successful boot
                $this->dispatcher->dispatch(new ModuleBootEvent(
                    $module,
                    true,
                    null,
                    ['module' => $module]
                ));
                
            } catch (\Throwable $e) {
                // Dispatch boot error event
                $this->dispatcher->dispatch(new ModuleBootEvent(
                    $module,
                    false,
                    $e,
                    ['module' => $module]
                ));
                
                // Re-throw the exception to maintain backward compatibility
                throw $e;
            }
        }
    }
    
    /**
     * Get a module by its class name
     * 
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws \OutOfBoundsException If the module is not found
     */
    public function getModule(string $moduleClass): ModuleInterface
    {
        return $this->registry->get($moduleClass);
    }
    
    /**
     * Check if a module is registered
     * 
     * @param string|ModuleInterface $module Module class name or instance
     */
    public function hasModule($module): bool
    {
        return $this->registry->has($module);
    }
    
    /**
     * Get all registered modules
     * 
     * @return array<class-string, ModuleInterface>
     */
    public function getModules(): array
    {
        return $this->registry->all();
    }
    
    /**
     * Get modules that implement a specific interface
     * 
     * @template T of object
     * @param class-string<T> $interface
     * @return array<class-string, T>
     */
    public function getByInterface(string $interface): array
    {
        return $this->registry->getByInterface($interface);
    }
}
