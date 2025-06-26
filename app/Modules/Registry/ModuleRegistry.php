<?php declare(strict_types=1);

namespace App\Modules\Registry;

use App\Modules\ModuleInterface;
use OutOfBoundsException;
use RuntimeException;

class ModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<class-string, ModuleInterface> */
    private array $modules = [];
    
    /** @var array<string, array<class-string>> */
    private array $tags = [];
    
    public function register(ModuleInterface $module): void
    {
        $moduleClass = get_class($module);
        
        if ($this->has($moduleClass)) {
            throw new RuntimeException(sprintf('Module %s is already registered', $moduleClass));
        }
        
        $this->modules[$moduleClass] = $module;
    }
    
    public function get(string $moduleClass): ModuleInterface
    {
        if (!$this->has($moduleClass)) {
            throw new OutOfBoundsException(sprintf('Module %s is not registered', $moduleClass));
        }
        
        return $this->modules[$moduleClass];
    }
    
    public function has($module): bool
    {
        $moduleClass = $module instanceof ModuleInterface ? get_class($module) : $module;
        return isset($this->modules[$moduleClass]);
    }
    
    public function all(): array
    {
        return $this->modules;
    }
    
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
    
    public function getByInterface(string $interface): array
    {
        if (!interface_exists($interface) && !class_exists($interface)) {
            throw new RuntimeException(sprintf('Interface or class %s does not exist', $interface));
        }
        
        $result = [];
        
        foreach ($this->modules as $moduleClass => $module) {
            if (is_a($module, $interface, true)) {
                $result[$moduleClass] = $module;
            }
        }
        
        return $result;
    }
    
    /**
     * Add a tag to a module
     * 
     * @internal For use by ModuleManager only
     */
    public function tagModule(string $moduleClass, string $tag): void
    {
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }
        
        if (!in_array($moduleClass, $this->tags[$tag], true)) {
            $this->tags[$tag][] = $moduleClass;
        }
    }
}
