<?php declare(strict_types=1);

namespace App\Modules;

use App\Modules\Discovery\ModuleDiscovery;
use App\Modules\Loader\ModuleLoader;
use App\Modules\Registry\ModuleRegistry;
use App\Modules\Dependency\DependencyResolver;
use App\Modules\Validation\DependencyValidator;
use App\Modules\Validation\VersionValidator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

/** Facade for module system operations */
class ModuleManager
{
    private ModuleRegistry $registry;
    private ModuleDiscovery $discovery;
    private ModuleLoader $loader;
    private DependencyResolver $resolver;
    
    public function __construct(
        private ContainerInterface $container,
        private string $modulesPath,
        private bool $enableCache = true,
        ?DependencyValidator $dependencyValidator = null,
        ?VersionValidator $versionValidator = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->registry = new ModuleRegistry();
        $this->discovery = new ModuleDiscovery($modulesPath, $enableCache);
        $this->loader = new ModuleLoader($container, $this->registry);
        $this->resolver = new DependencyResolver($this->registry);
        $this->dependencyValidator = $dependencyValidator ?? new DependencyValidator();
        $this->versionValidator = $versionValidator ?? new VersionValidator();
        $this->dispatcher = $dispatcher;
    }
    
    /** @return array<class-string> Discovered module class names */
    public function discoverModules(): array
    {
        return $this->discovery->discover();
    }
    
    /**
     * Register a module by class name with validation
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws RuntimeException If registration or validation fails
     */
    public function registerModule(string $moduleClass): ModuleInterface
    {
        if (!class_exists($moduleClass)) {
            throw new RuntimeException(sprintf('Module class %s does not exist', $moduleClass));
        }

        if (!is_a($moduleClass, ModuleInterface::class, true)) {
            throw new RuntimeException(sprintf(
                'Module %s must implement %s',
                $moduleClass,
                ModuleInterface::class
            ));
        }

        // Load the module
        $module = $this->loader->load($moduleClass);
        $metadata = $module->getMetadata();

        try {
            // Validate requirements
            $this->validateModuleRequirements($metadata);
            
            // Register the module
            $this->registry->register($module);
            
            // Validate dependencies against already registered modules
            $this->validateModuleDependencies($metadata);
            
            return $module;
        } catch (\Throwable $e) {
            $this->registry->unregister($moduleClass);
            throw new RuntimeException(
                sprintf('Failed to register module %s: %s', $moduleClass, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Validate module requirements
     * @throws RuntimeException If requirements are not met
     */
    private function validateModuleRequirements(ModuleMetadata $metadata): void
    {
        $requirements = $metadata->getRequirements();
        
        // Validate PHP version
        if (isset($requirements['php'])) {
            $this->versionValidator->validatePhpVersion($requirements['php']);
        }
        
        // Validate extensions
        $this->versionValidator->validateExtensions($requirements);
    }
    
    /**
     * Validate module dependencies against registered modules
     * @throws RuntimeException If dependencies are not met or conflicts exist
     */
    private function validateModuleDependencies(ModuleMetadata $metadata): void
    {
        $registeredModules = [];
        foreach ($this->registry->all() as $module) {
            $registeredModules[get_class($module)] = $module->getMetadata();
        }
        
        // Check for conflicts
        $this->dependencyValidator->validateNoConflicts($metadata, $registeredModules);
        
        // Check required dependencies
        $this->dependencyValidator->validateDependencies($metadata, $registeredModules);
        
        // Check optional dependencies (just log any issues)
        $missingOptional = $this->dependencyValidator->validateOptionalDependencies($metadata, $registeredModules);
        if (!empty($missingOptional)) {
            // Log missing optional dependencies if needed
            error_log(sprintf(
                'Module %s is missing optional dependencies: %s',
                $metadata->getId(),
                implode(', ', array_map(
                    fn($dep, $constraint) => "$dep ($constraint)",
                    array_keys($missingOptional),
                    $missingOptional
                ))
            ));
        }
    }
    
    /**
     * Register multiple modules
     * @param array<class-string> $moduleClasses
     * @return array<ModuleInterface>
     */
    public function registerModules(array $moduleClasses): array
    {
        return array_map(
            fn($class) => $this->registerModule($class),
            $moduleClasses
        );
    }
    
    /** @return array<ModuleInterface> Modules in dependency order */
    public function resolveDependencies(): array
    {
        $modules = $this->registry->all();
        if (empty($modules)) {
            return [];
        }
        
        $tempModule = new class implements ModuleInterface {
            public function register(): void {}
            public function boot(): void {}
            public function getDependencies(): array { return []; }
            public function getId(): string { return 'dependency.resolver'; }
            public function getName(): string { return 'Dependency Resolver'; }
        };
        
        $event = new ModuleDependencyEvent($tempModule, array_keys($modules));
        $this->dispatcher->dispatch($event);
        
        if ($event->isPropagationStopped()) {
            throw new RuntimeException(sprintf(
                'Dependency resolution stopped: %s',
                $event->getError()?->getMessage() ?? 'No reason provided'
            ));
        }
        
        try {
            $resolved = $this->resolver->resolveDependencies($modules);
            $this->dispatcher->dispatch(
                new ModuleDependencyEvent($tempModule, array_keys($resolved))
            );
            return $resolved;
        } catch (\Throwable $e) {
            $this->dispatcher->dispatch(new ModuleDependencyEvent(
                $tempModule, array_keys($modules), [], [], $e
            ));
            throw $e;
        }
    }
    
    /** Boot all registered modules */
    public function boot(): void
    {
        foreach ($this->registry->all() as $module) {
            try {
                $event = new ModuleBootEvent($module, false, null, ['module' => $module]);
                $this->dispatcher->dispatch($event);
                
                if ($event->isPropagationStopped()) {
                    continue;
                }
                
                $module->boot();
                $this->dispatcher->dispatch(new ModuleBootEvent(
                    $module, true, null, ['module' => $module]
                ));
                
            } catch (\Throwable $e) {
                $this->dispatcher->dispatch(new ModuleBootEvent(
                    $module, false, $e, ['module' => $module]
                ));
                throw $e;
            }
        }
    }
    
    /**
     * Get a module by its class name
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws \OutOfBoundsException If module not found
     */
    public function getModule(string $moduleClass): ModuleInterface
    {
        return $this->registry->get($moduleClass);
    }
    
    /** @param string|ModuleInterface $module Class name or instance */
    public function hasModule($module): bool
    {
        return $this->registry->has($module);
    }
    
    /** @return array<class-string, ModuleInterface> All registered modules */
    public function getModules(): array
    {
        return $this->registry->all();
    }
    
    /**
     * Get modules implementing a specific interfaces
     * @template T of object
     * @param class-string<T> $interface
     * @return array<class-string, T>
     */
    public function getByInterface(string $interface): array
    {
        return $this->registry->getByInterface($interface);
    }
}
