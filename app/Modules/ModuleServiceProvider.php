<?php declare(strict_types=1);

namespace App\Modules;

use Psr\Container\ContainerInterface;

class ModuleServiceProvider
{
    private ContainerInterface $container;
    private array $modules = [];
    private array $bootedModules = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register all modules
     */
    public function register(array $modules): void
    {
        foreach ($modules as $moduleClass) {
            if (!class_exists($moduleClass)) {
                throw new \RuntimeException("Module class {$moduleClass} does not exist");
            }

            /** @var Module $module */
            $module = new $moduleClass($this->container);
            $module->register();
            $this->modules[$moduleClass] = $module;
        }
    }

    /**
     * Boot all registered modules
     */
    public function boot(): void
    {
        foreach ($this->modules as $moduleClass => $module) {
            if (!isset($this->bootedModules[$moduleClass])) {
                $module->boot();
                $this->bootedModules[$moduleClass] = true;
            }
        }
    }

    /**
     * Get all registered modules
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get a specific module by class name
     */
    public function getModule(string $moduleClass): ?Module
    {
        return $this->modules[$moduleClass] ?? null;
    }
}
