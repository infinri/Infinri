<?php declare(strict_types=1);

namespace App\Modules\Loader;

use App\Modules\ModuleInterface;
use App\Modules\Registry\ModuleRegistryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ModuleLoader
{
    private ContainerInterface $container;
    private ModuleRegistryInterface $registry;
    
    public function __construct(
        ContainerInterface $container,
        ModuleRegistryInterface $registry
    ) {
        $this->container = $container;
        $this->registry = $registry;
    }
    
    /**
     * Load and initialize a module
     * 
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws RuntimeException If the module cannot be loaded
     */
    public function load(string $moduleClass): ModuleInterface
    {
        if ($this->registry->has($moduleClass)) {
            return $this->registry->get($moduleClass);
        }
        
        try {
            // Create and register the module
            $module = $this->createModule($moduleClass);
            $this->registry->register($module);
            
            // Register module services
            $module->register();
            
            return $module;
            
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf(
                'Failed to load module %s: %s',
                $moduleClass,
                $e->getMessage()
            ), 0, $e);
        }
    }
    
    /**
     * Boot all registered modules
     * 
     * @throws RuntimeException If a module fails to boot
     */
    public function bootAll(): void
    {
        foreach ($this->registry->all() as $module) {
            $this->boot($module);
        }
    }
    
    /**
     * Boot a module
     * 
     * @throws RuntimeException If the module fails to boot
     */
    public function boot(ModuleInterface $module): void
    {
        try {
            $module->boot();
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf(
                'Failed to boot module %s: %s',
                get_class($module),
                $e->getMessage()
            ), 0, $e);
        }
    }
    
    /**
     * Create a module instance
     * 
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws RuntimeException If the module cannot be instantiated
     */
    private function createModule(string $moduleClass): ModuleInterface
    {
        if (!class_exists($moduleClass)) {
            throw new RuntimeException(sprintf('Module class %s does not exist', $moduleClass));
        }
        
        try {
            $module = new $moduleClass($this->container);
            
            if (!$module instanceof ModuleInterface) {
                throw new RuntimeException(sprintf(
                    'Module %s must implement %s',
                    $moduleClass,
                    ModuleInterface::class
                ));
            }
            
            return $module;
            
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf(
                'Failed to instantiate module %s: %s',
                $moduleClass,
                $e->getMessage()
            ), 0, $e);
        }
    }
}
