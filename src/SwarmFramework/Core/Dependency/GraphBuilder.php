<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Dependency;

use Infinri\SwarmFramework\Core\Common\BaseValidator;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Dependency Graph Builder - Focused graph construction
 * 
 * Single responsibility: Build and maintain dependency graphs
 * Extracted from monolithic DependencyResolver for modularity.
 */
final class GraphBuilder extends BaseValidator
{
    use LoggerTrait;

    private array $graph = [];

    public function buildGraph(array $modules): ValidationResult
    {
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('dependency_graph_build', ['module_count' => count($modules)]);

        try {
            $this->graph = [];
            $errors = [];

            // Build initial graph structure
            foreach ($modules as $moduleName => $manifest) {
                $this->graph[$moduleName] = [
                    'dependencies' => $manifest->getDependencies(),
                    'dependents' => [],
                    'depth' => 0,
                    'resolved' => false
                ];
            }

            // Calculate reverse dependencies
            foreach ($this->graph as $moduleName => $moduleData) {
                foreach (array_keys($moduleData['dependencies']) as $depName) {
                    if (isset($this->graph[$depName])) {
                        $this->graph[$depName]['dependents'][] = $moduleName;
                    } else {
                        $errors[] = "Missing dependency: {$moduleName} requires {$depName}";
                    }
                }
            }

            $this->logOperationComplete('dependency_graph_build', [
                'nodes' => count($this->graph),
                'edges' => array_sum(array_map('count', $this->graph)),
                'duration_ms' => PerformanceTimer::formatDuration($startTime)
            ]);

            return empty($errors) 
                ? ValidationResultFactory::createSuccess()
                : ValidationResultFactory::createFailure($errors);

        } catch (\Throwable $e) {
            $this->logOperationFailure('dependency_graph_build', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ValidationResultFactory::createFailure(["Graph build failed: {$e->getMessage()}"]);
        }
    }

    public function getGraph(): array
    {
        return $this->graph;
    }

    public function getModuleDependencies(string $moduleName): array
    {
        return array_keys($this->graph[$moduleName]['dependencies'] ?? []);
    }

    public function getModuleDependents(string $moduleName): array
    {
        return $this->graph[$moduleName]['dependents'] ?? [];
    }

    public function hasModule(string $moduleName): bool
    {
        return isset($this->graph[$moduleName]);
    }

    public function getModuleCount(): int
    {
        return count($this->graph);
    }
}
