<?php declare(strict_types=1);

namespace App\Modules\Registry;

use App\Modules\ModuleInterface;
use OutOfBoundsException;
use RuntimeException;

/** Manages module instances and their metadata */
class ModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<class-string, ModuleInterface> Registered module instances */
    private array $modules = [];
    
    /** @var array<string, array<class-string>> Module tags for categorization [tag => [class1, ...]] */
    private array $tags = [];
    
    /**
     * Register a module instance
     * @throws RuntimeException If module is already registered
     */
    public function register(ModuleInterface $module): void
    {
        $moduleClass = get_class($module);
        
        if ($this->has($moduleClass)) {
            throw new RuntimeException(sprintf('Module %s is already registered', $moduleClass));
        }
        
        $this->modules[$moduleClass] = $module;
    }
    
    /**
     * Get a module by class name
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws OutOfBoundsException If module not found
     */
    public function get(string $moduleClass): ModuleInterface
    {
        if (!$this->has($moduleClass)) {
            throw new OutOfBoundsException(sprintf('Module %s not found', $moduleClass));
        }
        
        return $this->modules[$moduleClass];
    }
    
    /** @param string|ModuleInterface $module Class name or instance */
    public function has($module): bool
    {
        $moduleClass = $module instanceof ModuleInterface ? get_class($module) : $module;
        return isset($this->modules[$moduleClass]);
    }
    
    /** @return array<class-string, ModuleInterface> All registered modules */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Unregister a module by class name
     * @param class-string $moduleClass
     */
    public function unregister(string $moduleClass): void
    {
        if (!$this->has($moduleClass)) {
            return;
        }
        unset($this->modules[$moduleClass]);
        // Remove from tag index
        foreach ($this->tags as $tag => $classes) {
            $this->tags[$tag] = array_values(array_filter($classes, fn($c) => $c !== $moduleClass));
            if (empty($this->tags[$tag])) {
                unset($this->tags[$tag]);
            }
        }
    }
    
    /** @return array<class-string, ModuleInterface> Modules with the given tag */
    public function getByTag(string $tag): array
    {
        $result = [];
        
        foreach ($this->tags[$tag] ?? [] as $moduleClass) {
            if ($this->has($moduleClass)) {
                $result[$moduleClass] = $this->get($moduleClass);
            }
        }
        
        return $result;
    }
    
    /**
     * Get modules implementing an interface
     * @template T of object
     * @param class-string<T> $interface
     * @return array<class-string, T>
     * @throws RuntimeException If interface/class doesn't exist
     */
    public function getByInterface(string $interface): array
    {
        if (!interface_exists($interface) && !class_exists($interface)) {
            throw new RuntimeException(sprintf('Interface/class %s not found', $interface));
        }
        
        return array_filter(
            $this->modules,
            fn($module) => is_a($module, $interface, true)
        );
    }
    
    /**
     * Add a tag to a module
     * @param class-string $moduleClass
     * @throws OutOfBoundsException If module not registered
     * @internal For ModuleManager use only
     */
    public function tagModule(string $moduleClass, string $tag): void
    {
        if (!$this->has($moduleClass)) {
            throw new OutOfBoundsException(sprintf('Module %s not registered', $moduleClass));
        }
        
        $this->tags[$tag] ??= [];
        
        if (!in_array($moduleClass, $this->tags[$tag], true)) {
            $this->tags[$tag][] = $moduleClass;
        }
    }
}
