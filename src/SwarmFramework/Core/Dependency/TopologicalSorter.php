<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Dependency;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Psr\Log\LoggerInterface;

/**
 * Topological Sorter - Single responsibility sorting
 * 
 * Focused solely on topological sorting of dependency graphs.
 * Extracted from monolithic DependencyResolver for modularity.
 */
final class TopologicalSorter
{
    use LoggerTrait;

    private array $graph;

    public function __construct(LoggerInterface $logger, array $graph = [])
    {
        $this->logger = $logger;
        $this->graph = $graph;
    }

    public function setGraph(array $graph): void
    {
        $this->graph = $graph;
    }

    public function sort(): array
    {
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('topological_sort');

        try {
            $sorted = [];
            $visited = [];
            $tempMark = [];

            foreach (array_keys($this->graph) as $moduleName) {
                if (!isset($visited[$moduleName])) {
                    $this->visit($moduleName, $visited, $tempMark, $sorted);
                }
            }

            // Reverse to get correct dependency order (dependencies first)
            $result = array_reverse($sorted);

            $this->logOperationComplete('topological_sort', [
                'sorted_count' => count($sorted),
                'duration_ms' => PerformanceTimer::formatDuration($startTime)
            ]);

            return $result;

        } catch (\Throwable $e) {
            $this->logOperationFailure('topological_sort', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getParallelGroups(): array
    {
        $sorted = $this->sort();
        $groups = [];
        $processed = [];

        foreach ($sorted as $moduleName) {
            if (isset($processed[$moduleName])) {
                continue;
            }

            $group = [$moduleName];
            $dependencies = $this->getModuleDependencies($moduleName);

            // Find modules that can be loaded in parallel (same dependency level)
            foreach ($sorted as $otherModule) {
                if ($otherModule === $moduleName || isset($processed[$otherModule])) {
                    continue;
                }

                $otherDeps = $this->getModuleDependencies($otherModule);
                
                // Can be parallel if no interdependencies
                if (!$this->hasInterdependency($dependencies, $otherDeps)) {
                    $group[] = $otherModule;
                }
            }

            foreach ($group as $module) {
                $processed[$module] = true;
            }

            $groups[] = $group;
        }

        return $groups;
    }

    private function visit(string $moduleName, array &$visited, array &$tempMark, array &$sorted): void
    {
        if (isset($tempMark[$moduleName])) {
            throw ExceptionFactory::runtime(
                'Circular dependency detected',
                ['cycle' => $visited, 'module_name' => $moduleName]
            );
        }

        if (isset($visited[$moduleName])) {
            return;
        }

        $tempMark[$moduleName] = true;

        // Visit all dependencies first
        $dependencies = array_keys($this->graph[$moduleName]['dependencies'] ?? []);
        foreach ($dependencies as $depName) {
            if (isset($this->graph[$depName])) {
                $this->visit($depName, $visited, $tempMark, $sorted);
            }
        }

        unset($tempMark[$moduleName]);
        $visited[$moduleName] = true;
        $sorted[] = $moduleName;
    }

    private function getModuleDependencies(string $moduleName): array
    {
        return array_keys($this->graph[$moduleName]['dependencies'] ?? []);
    }

    private function hasInterdependency(array $deps1, array $deps2): bool
    {
        return !empty(array_intersect($deps1, $deps2));
    }
}
