<?php declare(strict_types=1);

namespace App\Providers;

use App\Modules\ModuleManager;
use Psr\Container\ContainerInterface;

/** Handles module registration and bootstrapping */
class ModuleServiceProvider
{
    /** @var array<string> Registered module class names */
    private array $moduleClasses = [];
    
    public function __construct(
        private ContainerInterface $container,
        private string $modulesPath
    ) {}
    
    /**
     * Register modules with the application
     * @param array<string>|null $moduleClasses Optional array of module class names to register
     * @return array<string> Registered module class names
     * @throws \RuntimeException If module registration fails
     */
    public function register(array $moduleClasses = null): array
    {
        $moduleManager = new ModuleManager($this->container, $this->modulesPath);
        
        // Discover modules if none provided
        $moduleClasses ??= $moduleManager->discoverModules();
        
        foreach ($moduleClasses as $moduleClass) {
            try {
                $moduleManager->registerModule($moduleClass);
                $this->moduleClasses[] = $moduleClass;
            } catch (\RuntimeException $e) {
                $this->logError("Failed to register module {$moduleClass}", $e);
                throw $e;
            }
        }
        
        $moduleManager->registerAll();
        return $this->moduleClasses;
    }
    
    /** Boot all registered modules */
    public function boot(): void
    {
        if (empty($this->moduleClasses)) {
            return;
        }
        
        $moduleManager = new ModuleManager($this->container, $this->modulesPath);
        
        // Re-register modules for booting
        foreach ($this->moduleClasses as $moduleClass) {
            try {
                $moduleManager->registerModule($moduleClass);
            } catch (\RuntimeException $e) {
                $this->logError("Failed to register module for boot: {$moduleClass}", $e);
            }
        }
        
        $moduleManager->bootAll();
    }
    
    /** @return array<string> Registered module class names */
    public function getRegisteredModules(): array
    {
        return $this->moduleClasses;
    }
    
    /** Log error if logger is available */
    private function logError(string $message, \Throwable $e): void
    {
        if ($this->container->has(\Psr\Log\LoggerInterface::class)) {
            $this->container->get(\Psr\Log\LoggerInterface::class)->error(
                $message . ': ' . $e->getMessage()
            );
        }
    }
}
