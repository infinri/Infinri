<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Dependency;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Utils\VersionUtil;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Version Validator - Single responsibility version checking
 * 
 * Focused solely on validating version constraints in dependency graphs.
 * Extracted from monolithic DependencyResolver for modularity.
 */
final class VersionValidator
{
    use LoggerTrait;

    private array $graph;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('VersionValidator', $config);
        $this->graph = [];
    }

    public function setGraph(array $graph): void
    {
        $this->graph = $graph;
    }

    public function validateConstraints(array $graph = []): ValidationResult
    {
        $workingGraph = $graph ?: $this->graph;
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('version_constraint_validation');

        try {
            $errors = [];

            foreach ($workingGraph as $moduleName => $moduleData) {
                $manifest = $moduleData['manifest'] ?? null;
                if (!$manifest) {
                    continue;
                }

                $dependencies = $moduleData['dependencies'] ?? [];
                
                foreach ($dependencies as $depName => $versionConstraint) {
                    if (!isset($workingGraph[$depName])) {
                        continue; // Missing dependency already handled by GraphBuilder
                    }

                    $depManifest = $workingGraph[$depName]['manifest'] ?? null;
                    if (!$depManifest) {
                        continue;
                    }

                    $depVersion = $depManifest->getVersion();
                    
                    if (!VersionUtil::satisfiesConstraint($depVersion, $versionConstraint)) {
                        $errors[] = "Version constraint violation: {$moduleName} requires {$depName} {$versionConstraint}, but {$depVersion} is available";
                    }
                }
            }

            $this->logOperationComplete('version_validation', [
                'modules_checked' => count($workingGraph),
                'violations_found' => count($errors)
            ]);

            return empty($errors) 
                ? ValidationResultFactory::success(['Version compatibility validated'])
                : ValidationResultFactory::failure($errors);

        } catch (\Throwable $e) {
            $this->logOperationFailure('version_validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ValidationResultFactory::failure(['Version validation failed: ' . $e->getMessage()]);
        }
    }

    public function validateSingleConstraint(string $version, string $constraint): bool
    {
        return VersionUtil::satisfiesConstraint($version, $constraint);
    }

    public function getConstraintViolations(): array
    {
        $result = $this->validateConstraints();
        return $result->isValid() ? [] : $result->getErrors();
    }
}
