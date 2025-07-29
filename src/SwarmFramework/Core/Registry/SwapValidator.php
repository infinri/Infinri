<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Swap Validator - Pre-swap validation logic
 * 
 * Focused solely on validating modules before hot-swap operations.
 * Extracted from monolithic HotSwapManager for modularity.
 */
#[Injectable(dependencies: ['LoggerInterface', 'ModuleValidator'])]
final class SwapValidator
{
    use LoggerTrait;

    private ModuleValidator $validator;
    private array $config;

    public function __construct(LoggerInterface $logger, ModuleValidator $validator, array $config = [])
    {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->config = ConfigManager::getConfig('SwapValidator', $config);
    }

    public function validatePreSwap(string $newModulePath, array $registeredModules): ValidationResult
    {
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('pre_swap_validation', ['path' => $newModulePath]);

        try {
            $errors = [];

            // Validate new module file exists and is readable
            if (!file_exists($newModulePath)) {
                $errors[] = "New module file does not exist: {$newModulePath}";
            } elseif (!is_readable($newModulePath)) {
                $errors[] = "New module file is not readable: {$newModulePath}";
            }

            if (!empty($errors)) {
                return ValidationResultFactory::failure($errors);
            }

            // Validate module manifest
            $manifestResult = $this->validator->validateModule($newModulePath, $registeredModules);
            if (!$manifestResult->isValid()) {
                return $manifestResult;
            }

            // Check for dependency conflicts
            $dependencyResult = $this->validateDependencyCompatibility($newModulePath, $registeredModules);
            if (!$dependencyResult->isValid()) {
                return $dependencyResult;
            }

            // Validate module integrity
            $integrityResult = $this->validateModuleIntegrity($newModulePath);
            if (!$integrityResult->isValid()) {
                return $integrityResult;
            }

            $this->logOperationComplete('pre_swap_validation', [
                'duration_ms' => PerformanceTimer::formatDuration($startTime)
            ]);
            return ValidationResultFactory::success(['Pre-swap validation passed']);

        } catch (\Throwable $e) {
            $this->logOperationFailure('validate_swap', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ValidationResultFactory::failure(["Pre-swap validation failed: {$e->getMessage()}"]);
        }
    }

    public function validatePostSwap(string $moduleName, array $registeredModules): ValidationResult
    {
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('post_swap_validation', ['module' => $moduleName]);

        try {
            $errors = [];

            // Check if module is properly registered
            if (!isset($registeredModules[$moduleName])) {
                $errors[] = "Module not found in registry after swap: {$moduleName}";
            }

            // Validate module manifest integrity
            $module = $registeredModules[$moduleName];
            $manifestPath = "/var/swarm/modules/{$moduleName}/swarm-module.json";
            $validationResult = $this->validator->validateModule(dirname($manifestPath), $registeredModules);
            if (!$validationResult->isValid()) {
                $errors[] = "Module manifest validation failed after swap: {$moduleName}";
            }

            // Check unit availability
            $unitValidation = $this->validateUnitsAvailable($module);
            if (!$unitValidation->isValid()) {
                $errors = array_merge($errors, $unitValidation->getErrors());
            }

            $this->logOperationComplete('validate_swap', [
                'module' => $moduleName,
                'version' => $module->getVersion(),
                'valid' => empty($errors),
                'duration_ms' => PerformanceTimer::formatDuration($startTime)
            ]);

            return empty($errors) 
                ? ValidationResultFactory::success(['Post-swap validation passed'])
                : ValidationResultFactory::failure($errors);

        } catch (\Throwable $e) {
            $this->logOperationFailure('post_swap_validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ValidationResultFactory::failure(["Post-swap validation failed: {$e->getMessage()}"]);
        }
    }

    private function validateDependencyCompatibility(string $newModulePath, array $registeredModules): ValidationResult
    {
        // Load new module manifest to check dependencies
        try {
            $manifestPath = $newModulePath . '/swarm-module.json';
            $manifest = ModuleManifest::fromJsonFile($manifestPath);
            $dependencies = $manifest->getDependencies();

            $errors = [];
            foreach ($dependencies as $depName => $versionConstraint) {
                if (!isset($registeredModules[$depName])) {
                    $errors[] = "Required dependency not found: {$depName}";
                    continue;
                }

                $depModule = $registeredModules[$depName];
                $depVersion = $depModule->getVersion();
                
                // Use VersionUtil for constraint checking
                if (!$this->satisfiesVersionConstraint($depVersion, $versionConstraint)) {
                    $errors[] = "Dependency version mismatch: {$depName} requires {$versionConstraint}, but {$depVersion} is available";
                }
            }

            return empty($errors) 
                ? ValidationResultFactory::success()
                : ValidationResultFactory::failure($errors);

        } catch (\Throwable $e) {
            return ValidationResultFactory::failure(["Dependency validation failed: {$e->getMessage()}"]);
        }
    }

    private function validateModuleIntegrity(string $modulePath): ValidationResult
    {
        try {
            // Check file integrity (size, permissions, etc.)
            $fileInfo = stat($modulePath);
            if ($fileInfo === false) {
                return ValidationResultFactory::failure(['Cannot read module file information']);
            }

            // Validate file size is reasonable
            if ($fileInfo['size'] > 10 * 1024 * 1024) { // 10MB limit
                return ValidationResultFactory::failure(['Module file too large (>10MB)']);
            }

            // Check PHP syntax
            $syntaxCheck = $this->validatePhpSyntax($modulePath);
            if (!$syntaxCheck->isValid()) {
                return $syntaxCheck;
            }

            return ValidationResultFactory::success();

        } catch (\Throwable $e) {
            return ValidationResultFactory::failure(["Module integrity check failed: {$e->getMessage()}"]);
        }
    }

    private function validatePhpSyntax(string $filePath): ValidationResult
    {
        $output = [];
        $returnCode = 0;
        
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            return ValidationResultFactory::failure(['PHP syntax error: ' . implode(' ', $output)]);
        }

        return ValidationResultFactory::success();
    }

    private function validateUnitsAvailable($module): ValidationResult
    {
        $errors = [];
        
        foreach ($module->getUnits() as $unitClass) {
            if (!class_exists($unitClass)) {
                $errors[] = "Unit class not available: {$unitClass}";
            }
        }

        return empty($errors) 
            ? ValidationResultFactory::success()
            : ValidationResultFactory::failure($errors);
    }

    private function satisfiesVersionConstraint(string $version, string $constraint): bool
    {
        // Simple version constraint checking - in production use composer/semver
        if ($constraint === '*') {
            return true;
        }

        return version_compare($version, $constraint, '>=');
    }
}
