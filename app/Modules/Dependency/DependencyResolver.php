<?php declare(strict_types=1);

namespace App\Modules\Dependency;

use App\Modules\ModuleInterface;
use App\Modules\Registry\ModuleRegistryInterface;
use App\Modules\Validation\VersionValidator;
use RuntimeException;

/** Resolves and validates module dependencies and loading order */
class DependencyResolver
{
    private ModuleRegistryInterface $registry;
    private VersionValidator $versionValidator;
    
    public function __construct(ModuleRegistryInterface $registry, ?VersionValidator $versionValidator = null)
    {
        $this->registry = $registry;
        $this->versionValidator = $versionValidator ?? new VersionValidator();
    }
    
    /**
     * Resolve module dependencies and return in correct load order
     * @param array<ModuleInterface> $modules
     * @return array<ModuleInterface>
     * @throws RuntimeException On circular/missing dependencies or version conflicts
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
     * @param array<string, bool> $visited Map of visited modules
     * @param array<string, bool> $visiting Map of modules being visited (cycle detection)
     * @param array<ModuleInterface> $sorted Output parameter for sorted modules
     * @throws RuntimeException On circular dependencies
     * @internal
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
     * Check if a required module dependency is satisfied
     * @param ModuleInterface $module Module with the dependency
     * @param class-string $dependency Required module class name
     * @param string $constraint Version constraint (e.g., '^1.0', '>=2.0 <3.0')
     * @throws RuntimeException If dependency is missing or version constraint fails
     * @see https://getcomposer.org/doc/articles/versions.md Version constraint syntax
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
    
    /** @throws RuntimeException If version constraint is not satisfied */
    private function checkVersionConstraint(
        ModuleInterface $requester,
        ModuleInterface $dependency,
        string $constraint
    ): void {
        if ($constraint === '*') {
            return; // No version constraint
        }
        
        $requiredVersion = $constraint instanceof \App\Modules\ValueObject\VersionConstraint ? (string)$constraint : $constraint;
        $installedVersion = $dependency->getVersion();
        
        // Use VersionValidator to evaluate constraint
        if ($this->versionValidator->satisfies($installedVersion, $requiredVersion)) {
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
