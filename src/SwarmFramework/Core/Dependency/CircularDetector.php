<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Dependency;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Psr\Log\LoggerInterface;

/**
 * Circular Dependency Detector - Single responsibility detector
 * 
 * Focused solely on detecting circular dependencies in graphs.
 * Extracted from monolithic DependencyResolver for modularity.
 */
final class CircularDetector
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

    public function detectCircularDependencies(): array
    {
        $startTime = PerformanceTimer::now();
        $this->logOperationStart('circular_dependency_detection');

        $cycles = [];
        $visited = [];
        $recursionStack = [];

        try {
            foreach (array_keys($this->graph) as $moduleName) {
                if (!isset($visited[$moduleName])) {
                    $path = [];
                    $cycle = $this->detectCycle($moduleName, $visited, $recursionStack, $path);
                    if ($cycle !== null) {
                        $cycles[] = $cycle;
                    }
                }
            }

            $this->logOperationComplete('circular_dependency_detection', [
                'cycles_found' => count($cycles),
                'duration_ms' => round((PerformanceTimer::now() - $startTime) * 1000, 2)
            ]);

            return $cycles;

        } catch (\Throwable $e) {
            $this->logOperationFailure('circular_dependency_detection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    } 

    public function hasCircularDependencies(): bool
    {
        return !empty($this->detectCircularDependencies());
    }

    private function detectCycle(string $moduleName, array &$visited, array &$recursionStack, array $path): ?string
    {
        if (isset($recursionStack[$moduleName])) {
            // Found cycle - build cycle path
            $cycleStart = array_search($moduleName, $path);
            $cyclePath = array_slice($path, $cycleStart);
            $cyclePath[] = $moduleName; // Complete the cycle
            return "Circular dependency: " . implode(' -> ', $cyclePath);
        }

        if (isset($visited[$moduleName])) {
            return null; // Already processed
        }

        $visited[$moduleName] = true;
        $recursionStack[$moduleName] = true;
        $path[] = $moduleName;

        // Check all dependencies
        $dependencies = array_keys($this->graph[$moduleName]['dependencies'] ?? []);
        foreach ($dependencies as $depName) {
            if (isset($this->graph[$depName])) {
                $cycle = $this->detectCycle($depName, $visited, $recursionStack, $path);
                if ($cycle !== null) {
                    return $cycle;
                }
            }
        }

        unset($recursionStack[$moduleName]);
        array_pop($path);
        
        return null;
    }
}
