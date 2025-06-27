<?php declare(strict_types=1);

namespace App\Modules\Validation;

use App\Modules\ModuleInterface;
use App\Modules\ValueObject\ModuleMetadata;
use App\Modules\ValueObject\VersionConstraint;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handles validation of module dependencies and conflicts.
 */
class DependencyValidator
{
    public function __construct(
        private VersionValidator $versionValidator = new VersionValidator()
    ) {}

    /**
     * Validate module dependencies.
     *
     * @param ModuleMetadata $module The module to validate
     * @param array<string,ModuleMetadata> $availableModules Map of available modules by class name
     * @throws RuntimeException If any dependency is not met
     */
    public function validateDependencies(ModuleMetadata $module, array $availableModules): void
    {
        $this->validateDependencyList(
            $module->getDependencies(),
            $availableModules,
            'Required dependency %s with version %s is not met. Available: %s',
            true
        );
    }

    /**
     * Validate optional module dependencies.
     *
     * @param ModuleMetadata $module The module to validate
     * @param array<string,ModuleMetadata> $availableModules Map of available modules by class name
     * @return array<class-string,string> List of missing optional dependencies
     */
    public function validateOptionalDependencies(ModuleMetadata $module, array $availableModules): array
    {
        $missing = [];
        
        foreach ($module->getOptionalDependencies() as $dependency => $constraint) {
            if (!isset($availableModules[$dependency]) || 
                !$this->isVersionCompatible($availableModules[$dependency], $constraint)) {
                $missing[$dependency] = $constraint;
            }
        }
        
        return $missing;
    }

    /**
     * Validate that there are no conflicts with other modules.
     *
     * @param ModuleMetadata $module The module to validate
     * @param array<string,ModuleMetadata> $installedModules Map of installed modules by class name
     * @throws RuntimeException If there are any conflicts
     */
    public function validateNoConflicts(ModuleMetadata $module, array $installedModules): void
    {
        // Check if this module conflicts with any installed modules
        foreach ($module->getConflicts() as $conflict => $constraint) {
            if (isset($installedModules[$conflict])) {
                $conflictingModule = $installedModules[$conflict];
                if ($this->isVersionCompatible($conflictingModule, $constraint)) {
                    throw new RuntimeException(sprintf(
                        'Module %s conflicts with %s %s',
                        $module->getId(),
                        $conflict,
                        $constraint
                    ));
                }
            }
        }

        // Check if any installed modules conflict with this one
        foreach ($installedModules as $installedModule) {
            $conflicts = $installedModule->getConflicts();
            $moduleClass = get_class($module);
            
            if (isset($conflicts[$moduleClass]) && 
                $this->isVersionCompatible($module, $conflicts[$moduleClass])) {
                throw new RuntimeException(sprintf(
                    'Module %s %s conflicts with %s %s',
                    $module->getId(),
                    $module->getVersion(),
                    $installedModule->getId(),
                    $conflicts[$moduleClass]
                ));
            }
        }
    }

    /**
     * Validate a list of dependencies.
     *
     * @param array<class-string,string|VersionConstraint> $dependencies Map of module class to version constraint
     * @param array<string,ModuleMetadata> $availableModules Map of available modules by class name
     * @param string $errorMessage Error message format (should accept module, constraint, and version)
     * @param bool $throw Whether to throw an exception on failure
     * @return bool True if all dependencies are satisfied
     * @throws RuntimeException If $throw is true and a dependency is not met
     */
    private function validateDependencyList(
        array $dependencies,
        array $availableModules,
        string $errorMessage,
        bool $throw = false
    ): bool {
        foreach ($dependencies as $dependency => $constraint) {
            if (!isset($availableModules[$dependency])) {
                if ($throw) {
                    throw new RuntimeException(sprintf(
                        'Required dependency %s is not available',
                        $dependency
                    ));
                }
                return false;
            }

            $dependencyModule = $availableModules[$dependency];
            if (!$this->isVersionCompatible($dependencyModule, $constraint)) {
                if ($throw) {
                    throw new RuntimeException(sprintf(
                        $errorMessage,
                        $dependency,
                        $constraint,
                        $dependencyModule->getVersion()
                    ));
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a module version is compatible with a constraint.
     */
    /**
 * @param ModuleMetadata $module
 * @param string|VersionConstraint $constraint
 */
private function isVersionCompatible(ModuleMetadata $module, string|VersionConstraint $constraint): bool
    {
        $constraintString = $constraint instanceof VersionConstraint ? (string)$constraint : $constraint;
    return $this->versionValidator->satisfies($module->getVersion(), $constraintString);
    }
}
