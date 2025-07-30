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
        $inStack = [];

        try {
            foreach (array_keys($this->graph) as $moduleName) {
                if (!isset($visited[$moduleName])) {
                    $cycle = $this->dfsDetectCycle($moduleName, $visited, $inStack, []);
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

    private function dfsDetectCycle(string $moduleName, array &$visited, array &$inStack, array $path): ?string
    {
        // Mark current node as visited and add to recursion stack
        $visited[$moduleName] = true;
        $inStack[$moduleName] = true;
        $path[] = $moduleName;

        // Get dependencies for this module
        $dependencies = array_keys($this->graph[$moduleName]['dependencies'] ?? []);
        
        foreach ($dependencies as $depName) {
            // If dependency is in current recursion stack, we found a cycle
            if (isset($inStack[$depName])) {
                $cycleStart = array_search($depName, $path);
                if ($cycleStart !== false) {
                    $cyclePath = array_slice($path, $cycleStart);
                    $cyclePath[] = $depName; // Complete the cycle
                    return "Circular dependency: " . implode(' -> ', $cyclePath);
                }
                return "Circular dependency detected: " . $moduleName . " -> " . $depName;
            }
            
            // If dependency hasn't been visited, recursively check it
            if (!isset($visited[$depName]) && isset($this->graph[$depName])) {
                $cycle = $this->dfsDetectCycle($depName, $visited, $inStack, $path);
                if ($cycle !== null) {
                    return $cycle;
                }
            }
        }

        // Remove from recursion stack before returning
        unset($inStack[$moduleName]);
        array_pop($path);
        
        return null;
    }
}
