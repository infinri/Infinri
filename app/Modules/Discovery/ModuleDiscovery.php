<?php declare(strict_types=1);

namespace App\Modules\Discovery;

use App\Modules\ModuleInterface;
use InvalidArgumentException;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Throwable;

/** Discovers and loads modules from the filesystem with caching support */
class ModuleDiscovery
{
    private string $modulesPath;
    private bool $useCache;
    private ?string $cacheFile;
    private ?LoggerInterface $logger;
    private bool $debug = false;
    
    /**
     * Create a new ModuleDiscovery instance.
     *
     * @param string $modulesPath Path to the directory containing modules
     * @param bool $useCache Whether to enable caching of discovery results
     * @param string|null $cacheFile Path to the cache file (defaults to system temp dir)
     *
     * @example
     * ```php
     * // Basic usage
     * $discovery = new ModuleDiscovery('/path/to/modules');
     *
     * // With custom cache file
     * $discovery = new ModuleDiscovery(
     *     '/path/to/modules',
     *     true,
     *     '/path/to/cache/modules.cache'
     * );
     * ```
     */
    /** @param string $modulesPath Path to the directory containing modules */
    public function __construct(
        string $modulesPath, 
        bool $useCache = true, 
        ?string $cacheFile = null,
        ?LoggerInterface $logger = null,
        bool $debug = false
    ) {
        $this->modulesPath = rtrim($modulesPath, '/\\');
        $this->useCache = $useCache;
        $this->cacheFile = $cacheFile ?: sys_get_temp_dir() . '/modules.cache';
        $this->logger = $logger;
        $this->debug = $debug;
    }
    
    /**
     * Discover all modules in the modules directory
     * @return array<class-string> Array of fully-qualified module class names
     * @throws RuntimeException If modules directory is invalid or inaccessible
     */
    public function discover(): array
    {
        // Check cache first if enabled
        if ($this->useCache && $this->isCacheValid()) {
            $cachedModules = $this->loadFromCache();
            $this->logDebug('Loaded {count} modules from cache', ['count' => count($cachedModules)]);
            return $cachedModules;
        }

        $modules = [];
        $dirs = [];

        $this->logDebug('Scanning directory: {path}', ['path' => $this->modulesPath]);
        
        // Scan the modules directory
        if (is_dir($this->modulesPath) && is_readable($this->modulesPath)) {
            $dirs = array_filter(scandir($this->modulesPath), function ($dir) {
                return $dir !== '.' && $dir !== '..' && is_dir($this->modulesPath . '/' . $dir);
            });
        }

        $this->logDebug('Found {count} potential module directories', ['count' => count($dirs)]);

        // Process each module directory
        foreach ($dirs as $dir) {
            $modulePath = $this->modulesPath . '/' . $dir;
            $moduleName = basename($dir);

            $this->logDebug('Processing module directory: {path}', ['path' => $modulePath]);

            // Look for a module class in the directory
            if ($moduleClass = $this->findModuleClass($modulePath, $moduleName)) {
                $this->logInfo('Discovered module: {class}', ['class' => $moduleClass]);
                $modules[] = $moduleClass;
            } else {
                $this->logDebug('No valid module class found in: {path}', ['path' => $modulePath]);
            }
        }

        // Cache the results if enabled
        if ($this->useCache) {
            $this->saveToCache($modules);
            $this->logDebug('Cached {count} modules', ['count' => count($modules)]);
        }

        return $modules;
    }
    
    private function logDebug(string $message, array $context = []): void
    {
        if ($this->debug && $this->logger) {
            $this->logger->debug('[ModuleDiscovery] ' . $message, $context);
        }
    }
    
    private function logInfo(string $message, array $context = []): void
    {
        $this->logger?->info('[ModuleDiscovery] ' . $message, $context);
    }
    
    private function logWarning(string $message, array $context = []): void
    {
        $this->logger?->warning('[ModuleDiscovery] ' . $message, $context);
    }
    
    private function logError(string $message, array $context = [], ?Throwable $exception = null): void
    {
        if ($exception) {
            $context['exception'] = $exception;
        }
        $this->logger?->error('[ModuleDiscovery] ' . $message, $context);
    }
    
    /**
     * Check if the module cache is still valid
     * @return bool True if cache is valid, false otherwise
     */
    private function isCacheValid(): bool
    {
        if (!file_exists($this->cacheFile)) {
            $this->logDebug('Cache file does not exist: {file}', ['file' => $this->cacheFile]);
            return false;
        }
        
        // Check if cache is older than modules directory
        $cacheTime = filemtime($this->cacheFile);
        $dirTime = filemtime($this->modulesPath);
        
        if ($dirTime > $cacheTime) {
            $this->logDebug('Modules directory modified after cache was created');
            return false;
        }
        
        // Check if any module file was modified after cache was created
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->modulesPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() > $cacheTime) {
                $this->logDebug('File modified after cache was created: {file}', ['file' => $file->getPathname()]);
                return false;
            }
        }
        
        $this->logDebug('Cache is valid');
        return true;
    }
    
    /** @return class-string<ModuleInterface>|null */
    private function findModuleClass(string $moduleDir, string $moduleName): ?string
    {
        // First try PSR-4 style (App\Modules\ModuleName\ModuleNameModule)
        $moduleClass = 'App\\Modules\\' . $moduleName . '\\' . $moduleName . 'Module';
        
        if (class_exists($moduleClass)) {
            if (is_subclass_of($moduleClass, ModuleInterface::class)) {
                $this->logDebug('Found PSR-4 module class: {class}', ['class' => $moduleClass]);
                return $moduleClass;
            }
            $this->logDebug('Class {class} exists but does not implement ModuleInterface', ['class' => $moduleClass]);
        }
        
        // Then try legacy style (ModuleName_ModuleName)
        $legacyClass = str_replace(' ', '_', ucwords(str_replace('_', ' ', $moduleName))) . '_' . $moduleName;
        
        if (class_exists($legacyClass)) {
            if (is_subclass_of($legacyClass, ModuleInterface::class)) {
                $this->logDebug('Found legacy module class: {class}', ['class' => $legacyClass]);
                return $legacyClass;
            }
            $this->logDebug('Class {class} exists but does not implement ModuleInterface', ['class' => $legacyClass]);
        }
        
        // Look for module files in the directory
        $possibleFiles = [
            $moduleDir . '/' . $moduleName . '.php',
            $moduleDir . '/src/' . $moduleName . '.php',
            $moduleDir . '/Module.php',
            $moduleDir . '/src/Module.php',
        ];
        
        $this->logDebug('Checking possible module files in {dir}:', ['dir' => $moduleDir]);
        
        foreach ($possibleFiles as $file) {
            $this->logDebug('  - {file}: {status}', [
                'file' => $file, 
                'status' => file_exists($file) ? 'EXISTS' : 'NOT FOUND'
            ]);
            
            if (file_exists($file)) {
                try {
                    $this->logDebug('Attempting to load file: {file}', ['file' => $file]);
                    
                    $classesBefore = get_declared_classes();
                    require_once $file;
                    $classesAfter = get_declared_classes();
                    $newClasses = array_diff($classesAfter, $classesBefore);
                    
                    error_log(sprintf('[ModuleDiscovery] New classes after including %s: %s', 
                        $file, 
                        !empty($newClasses) ? implode(', ', $newClasses) : 'NONE'
                    ));
                    
                    foreach ($newClasses as $class) {
                        error_log(sprintf('[ModuleDiscovery] Checking if %s implements ModuleInterface', $class));
                        if (is_subclass_of($class, ModuleInterface::class)) {
                            error_log(sprintf('[ModuleDiscovery] Found valid module class: %s', $class));
                            return $this->validateModuleClass($class) ? $class : null;
                        }
                    }
                } catch (\Throwable $e) {
                    // Skip invalid module files
                    error_log(sprintf('[ModuleDiscovery] Error loading module file %s: %s', $file, $e->getMessage()));
                    continue;
                }
            }
        }
        
        // Last resort: Try to find any class in the expected namespace
        $possibleClasses = [
            "Test\\Modules\\{$moduleName}\\{$moduleName}Module",
            "Test\\Modules\\{$moduleName}\\{$moduleName}",
            "App\\Modules\\{$moduleName}\\{$moduleName}Module",
            "App\\Modules\\{$moduleName}\\{$moduleName}",
            $moduleName
        ];
        
        foreach ($possibleClasses as $class) {
            if (class_exists($class, false) && is_subclass_of($class, ModuleInterface::class)) {
                return $this->validateModuleClass($class) ? $class : null;
            }
        }
        
        return null;
    }
    
    /**
     * Validate that a class is a valid module implementation
     * @param class-string $className
     * @throws RuntimeException If class exists but doesn't implement ModuleInterface
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
     * @return array<class-string>|null
     * @throws RuntimeException If cache file exists but is not readable
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
    
    /** @param array<class-string> $modules */
    private function saveToCache(array $modules): void
    {
        @file_put_contents($this->cacheFile, json_encode($modules, JSON_PRETTY_PRINT));
    }
}
