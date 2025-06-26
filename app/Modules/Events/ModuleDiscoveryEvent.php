<?php declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\ModuleInterface;

/**
 * Event triggered during module discovery
 */
class ModuleDiscoveryEvent extends ModuleEvent
{
    public const string BEFORE_DISCOVERY = 'module.discovery.before';
    public const string AFTER_DISCOVERY = 'module.discovery.after';
    
    /** @var array<class-string> */
    private array $modules = [];
    
    public function __construct(
        private string $modulesPath,
        bool $fromCache = false,
        ?ModuleInterface $module = null,
        array $arguments = []
    ) {
        parent::__construct($module ?? new class implements ModuleInterface {
            public function register(): void {}
            public function boot(): void {}
            public function getDependencies(): array { return []; }
        }, $arguments);
        
        $this->setArgument('from_cache', $fromCache);
    }
    
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }
    
    /**
     * @return array<class-string>
     */
    public function getDiscoveredModules(): array
    {
        return $this->modules;
    }
    
    /**
     * @param array<class-string> $modules
     */
    public function setDiscoveredModules(array $modules): void
    {
        $this->modules = $modules;
    }
    
    public function isFromCache(): bool
    {
        return (bool)$this->getArgument('from_cache', false);
    }
}
