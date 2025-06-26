<?php declare(strict_types=1);

namespace App\Modules\Discovery;

use App\Modules\ModuleInterface;
use InvalidArgumentException;
use RuntimeException;

class ModuleDiscovery
{
    private string $modulesPath;
    private bool $useCache;
    private ?string $cacheFile;
    
    public function __construct(string $modulesPath, bool $useCache = true, ?string $cacheFile = null)
    {
        $this->modulesPath = rtrim($modulesPath, '/\\');
        $this->useCache = $useCache;
        $this->cacheFile = $cacheFile ?: sys_get_temp_dir() . '/modules.cache';
    }
    
    /**
     * Discover all modules in the modules directory
     * 
     * @return array<class-string> Array of module class names
     * @throws RuntimeException If the modules directory is invalid
     */
    public function discover(): array
    {
        if (!is_dir($this->modulesPath)) {
            throw new RuntimeException(sprintf('Modules directory not found: %s', $this->modulesPath));
        }
        
        if ($this->useCache && $cached = $this->loadFromCache()) {
            return $cached;
        }
        
        $moduleDirs = glob($this->modulesPath . '/*', GLOB_ONLYDIR);
        $modules = [];
        
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);
            $moduleClass = $this->findModuleClass($moduleDir, $moduleName);
            
            if ($moduleClass !== null) {
                $modules[] = $moduleClass;
            }
        }
        
        // Sort modules by name for consistent loading order
        sort($modules);
        
        if ($this->useCache) {
            $this->saveToCache($modules);
        }
        
        return $modules;
    }
    
    /**
     * Find the module class in a module directory
     */
    private function findModuleClass(string $moduleDir, string $moduleName): ?string
    {
        // Try the PSR-4 standard location first
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";
        
        if (class_exists($moduleClass)) {
            return $this->validateModuleClass($moduleClass) ? $moduleClass : null;
        }
        
        // Look for module.php or Module.php as fallback
        $possibleFiles = [
            "{$moduleDir}/module.php",
            "{$moduleDir}/Module.php",
            "{$moduleDir}/src/{$moduleName}Module.php"
        ];
        
        foreach ($possibleFiles as $file) {
            if (file_exists($file)) {
                try {
                    $classesBefore = get_declared_classes();
                    require_once $file;
                    $classesAfter = get_declared_classes();
                    $newClasses = array_diff($classesAfter, $classesBefore);
                    
                    foreach ($newClasses as $class) {
                        if (is_subclass_of($class, ModuleInterface::class)) {
                            return $this->validateModuleClass($class) ? $class : null;
                        }
                    }
                } catch (\Throwable $e) {
                    // Skip invalid module files
                    continue;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Validate that a class is a valid module
     */
    private function validateModuleClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }
        
        $reflection = new \ReflectionClass($className);
        
        return !$reflection->isAbstract() && 
               $reflection->implementsInterface(ModuleInterface::class);
    }
    
    /**
     * Load module class names from cache
     */
    private function loadFromCache(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        
        $data = @file_get_contents($this->cacheFile);
        if ($data === false) {
            return null;
        }
        
        $decoded = @json_decode($data, true);
        if (!is_array($decoded)) {
            return null;
        }
        
        // Verify that all cached modules still exist
        $validModules = [];
        foreach ($decoded as $moduleClass) {
            if (class_exists($moduleClass) && 
                is_subclass_of($moduleClass, ModuleInterface::class)) {
                $validModules[] = $moduleClass;
            }
        }
        
        return $validModules;
    }
    
    /**
     * Save module class names to cache
     */
    private function saveToCache(array $modules): void
    {
        @file_put_contents($this->cacheFile, json_encode($modules, JSON_PRETTY_PRINT));
    }
}
