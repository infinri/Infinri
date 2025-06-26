<?php declare(strict_types=1);

namespace App\Providers;

use App\Modules\ModuleManager;
use Psr\Container\ContainerInterface;

/**
 * Service provider for module registration and bootstrapping
 */
class ModuleServiceProvider
{
    /** @var array<string> */
    private array $moduleClasses = [];
    
    public function __construct(
        private ContainerInterface $container,
        private string $modulesPath
    ) {}
    
    /**
     * Register modules with the application
     * 
     * @param array<string>|null $moduleClasses Optional array of module class names to register
     * @return array<string> Array of registered module class names
     */
    public function register(array $moduleClasses = null): array
    {
        $moduleManager = new ModuleManager($this->container, $this->modulesPath);
        
        // If no modules provided, discover them
        if ($moduleClasses === null) {
            $moduleClasses = $moduleManager->discoverModules();
        }
        
        // Register each module
        foreach ($moduleClasses as $moduleClass) {
            try {
                $moduleManager->registerModule($moduleClass);
                $this->moduleClasses[] = $moduleClass;
            } catch (\RuntimeException $e) {
                // Log error but continue with other modules
                if ($this->container->has(\Psr\Log\LoggerInterface::class)) {
                    $this->container->get(\Psr\Log\LoggerInterface::class)->error(
                        "Failed to register module {$moduleClass}: " . $e->getMessage()
                    );
                }
                throw $e;
            }
        }
        
        // Register all module services
        $moduleManager->registerAll();
        
        return $this->moduleClasses;
    }
    
    /**
     * Boot all registered modules
     */
    public function boot(): void
    {
        if (empty($this->moduleClasses)) {
            return;
        }
        
        $moduleManager = new ModuleManager($this->container, $this->modulesPath);
        
        // Re-register modules to ensure they're available for booting
        foreach ($this->moduleClasses as $moduleClass) {
            try {
                $moduleManager->registerModule($moduleClass);
            } catch (\RuntimeException $e) {
                // Log error but continue with other modules
                if ($this->container->has(\Psr\Log\LoggerInterface::class)) {
                    $this->container->get(\Psr\Log\LoggerInterface::class)->error(
                        "Failed to register module for boot: {$moduleClass} - " . $e->getMessage()
                    );
                }
            }
        }
        
        // Boot all modules
        $moduleManager->bootAll();
    }
    
    /**
     * Get the list of registered module classes
     * 
     * @return array<string>
     */
    public function getRegisteredModules(): array
    {
        return $this->moduleClasses;
    }
}
