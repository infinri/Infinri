<?php declare(strict_types=1);

namespace App\Modules\Registry;

use App\Modules\ModuleInterface;

interface ModuleRegistryInterface
{
    /**
     * Register a module instance
     * 
     * @throws \RuntimeException If the module is already registered
     */
    public function register(ModuleInterface $module): void;
    
    /**
     * Get a module by its class name
     * 
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws \OutOfBoundsException If the module is not found
     */
    public function get(string $moduleClass): ModuleInterface;
    
    /**
     * Check if a module is registered
     * 
     * @param string|ModuleInterface $module Module class name or instance
     */
    public function has($module): bool;
    
    /**
     * Get all registered modules
     * 
     * @return array<class-string, ModuleInterface>
     */
    public function all(): array;
    
    /**
     * Get modules by tag
     * 
     * @return array<ModuleInterface>
     */
    public function getByTag(string $tag): array;
    
    /**
     * Get modules that implement a specific interface
     * 
     * @template T of object
     * @param class-string<T> $interface
     * @return array<class-string, T>
     */
    public function getByInterface(string $interface): array;
}
