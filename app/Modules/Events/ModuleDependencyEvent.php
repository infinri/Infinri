<?php declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\ModuleInterface;

/**
 * Event triggered during module dependency resolution
 */
class ModuleDependencyEvent extends ModuleEvent
{
    public const string BEFORE_RESOLUTION = 'module.dependency.before_resolution';
    public const string AFTER_RESOLUTION = 'module.dependency.after_resolution';
    public const string RESOLUTION_ERROR = 'module.dependency.error';
    
    /** @var array<class-string> */
    private array $dependencies = [];
    
    /** @var array<class-string> */
    private array $missingDependencies = [];
    
    /** @var array<class-string, string> */
    private array $versionConflicts = [];
    
    /**
     * @param array<class-string> $dependencies
     */
    public function __construct(
        ModuleInterface $module,
        array $dependencies = [],
        array $missingDependencies = [],
        array $versionConflicts = [],
        ?\Throwable $error = null,
        array $arguments = []
    ) {
        parent::__construct($module, $arguments);
        
        $this->dependencies = $dependencies;
        $this->missingDependencies = $missingDependencies;
        $this->versionConflicts = $versionConflicts;
        
        if ($error) {
            $this->setError($error->getMessage());
            $this->setArgument('exception', $error);
        }
    }
    
    /**
     * @return array<class-string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    /**
     * @return array<class-string>
     */
    public function getMissingDependencies(): array
    {
        return $this->missingDependencies;
    }
    
    /**
     * @return array<class-string, string>
     */
    public function getVersionConflicts(): array
    {
        return $this->versionConflicts;
    }
    
    public function hasMissingDependencies(): bool
    {
        return !empty($this->missingDependencies);
    }
    
    public function hasVersionConflicts(): bool
    {
        return !empty($this->versionConflicts);
    }
    
    public function getError(): ?\Throwable
    {
        return $this->getArgument('exception');
    }
}
