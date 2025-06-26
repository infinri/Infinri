<?php declare(strict_types=1);

namespace App\Modules\Dependency;

use App\Modules\ModuleInterface;
use App\Modules\Registry\ModuleRegistryInterface;
use RuntimeException;

class DependencyResolver
{
    private ModuleRegistryInterface $registry;
    
    public function __construct(ModuleRegistryInterface $registry)
    {
        $this->registry = $registry;
    }
    
    /**
     * Resolve module dependencies and return modules in the correct order
     * 
     * @param array<ModuleInterface> $modules
     * @return array<ModuleInterface>
     * @throws RuntimeException If there are dependency issues
     */
    public function resolveDependencies(array $modules): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];
        
        foreach ($modules as $module) {
            $this->visitModule($module, $visited, $visiting, $sorted);
        }
        
        return $sorted;
    }
    
    /**
     * Visit a module and its dependencies (depth-first search)
     * 
     * @param array<string, bool> $visited
     * @param array<string, bool> $visiting
     * @param array<ModuleInterface> $sorted
     * @throws RuntimeException For circular dependencies or version conflicts
     */
    private function visitModule(
        ModuleInterface $module,
        array &$visited,
        array &$visiting,
        array &$sorted
    ): void {
        $moduleClass = get_class($module);
        
        if (isset($visited[$moduleClass])) {
            return;
        }
        
        if (isset($visiting[$moduleClass])) {
            throw new RuntimeException(sprintf(
                'Circular dependency detected involving module %s',
                $moduleClass
            ));
        }
        
        $visiting[$moduleClass] = true;
        
        // Check dependencies
        foreach ($module->getDependencies() as $dependency => $constraint) {
            $this->checkDependency($module, $dependency, $constraint);
            
            if ($depModule = $this->registry->get($dependency)) {
                $this->visitModule($depModule, $visited, $visiting, $sorted);
            }
        }
        
        // Check optional dependencies (don't fail if not found)
        foreach ($module->getOptionalDependencies() as $dependency => $constraint) {
            if ($this->registry->has($dependency)) {
                $depModule = $this->registry->get($dependency);
                $this->checkVersionConstraint($module, $depModule, $constraint);
                $this->visitModule($depModule, $visited, $visiting, $sorted);
            }
        }
        
        $visited[$moduleClass] = true;
        unset($visiting[$moduleClass]);
        
        // Add to sorted list
        if (!in_array($module, $sorted, true)) {
            $sorted[] = $module;
        }
    }
    
    /**
     * Check if a module dependency is satisfied
     * 
     * @throws RuntimeException If the dependency is not satisfied
     */
    private function checkDependency(
        ModuleInterface $module,
        string $dependency,
        string $constraint
    ): void {
        if (!$this->registry->has($dependency)) {
            throw new RuntimeException(sprintf(
                'Module %s requires %s %s but it is not installed',
                get_class($module),
                $dependency,
                $constraint ?: '*'
            ));
        }
        
        $depModule = $this->registry->get($dependency);
        $this->checkVersionConstraint($module, $depModule, $constraint);
    }
    
    /**
     * Check if a module version satisfies a constraint
     * 
     * @throws RuntimeException If the version constraint is not satisfied
     */
    private function checkVersionConstraint(
        ModuleInterface $requester,
        ModuleInterface $dependency,
        string $constraint
    ): void {
        if ($constraint === '*') {
            return; // No version constraint
        }
        
        $requiredVersion = $constraint;
        $installedVersion = $dependency->getVersion();
        
        // Simple version comparison for now
        // In a real implementation, use something like composer/semver
        if (version_compare($installedVersion, $requiredVersion, '>=')) {
            return;
        }
        
        throw new RuntimeException(sprintf(
            'Module %s requires %s %s but version %s is installed',
            get_class($requester),
            get_class($dependency),
            $requiredVersion,
            $installedVersion
        ));
    }
}
