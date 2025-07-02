<?php declare(strict_types=1);

namespace App\Modules\Registry;

use App\Modules\ModuleInterface;

/** Defines the contract for module registration and retrieval */
interface ModuleRegistryInterface
{
    /** @throws \RuntimeException If module already registered */
    public function register(ModuleInterface $module): void;
    
    /**
     * Unregister a module by class name
     * @param class-string $moduleClass
     */
    public function unregister(string $moduleClass): void;
    
    /**
     * Get a module by class name
     * @template T of ModuleInterface
     * @param class-string<T> $moduleClass
     * @return T
     * @throws \OutOfBoundsException If module not found
     */
    public function get(string $moduleClass): ModuleInterface;
    
    /** @param string|ModuleInterface $module Class name or instance */
    public function has($module): bool;
    
    /** @return array<class-string, ModuleInterface> All registered modules */
    public function all(): array;
    
    /** @return array<class-string, ModuleInterface> Modules with the given tag */
    public function getByTag(string $tag): array;
    
    /**
     * Get modules implementing an interface
     * @template T of object
     * @param class-string<T> $interface
     * @return array<class-string, T>
     */
    public function getByInterface(string $interface): array;
}
