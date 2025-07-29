<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Dependency;

use Infinri\SwarmFramework\Core\Dependency\GraphBuilder;
use Infinri\SwarmFramework\Core\Dependency\TopologicalSorter;
use Infinri\SwarmFramework\Core\Dependency\CircularDetector;
use Infinri\SwarmFramework\Core\Dependency\VersionValidator;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Utils\VersionUtil;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Dependency Resolver - Clean coordinator for dependency operations
 * 
 * Delegates to specialized components following single responsibility.
 * Reduced from 505 lines to focused coordination logic.
 */
#[Injectable(dependencies: ['LoggerInterface'])]
final class DependencyResolver
{
    use LoggerTrait;

    private GraphBuilder $graphBuilder;
    private CircularDetector $circularDetector;
    private TopologicalSorter $sorter;
    private VersionValidator $versionValidator;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('DependencyResolver', $config);

        $this->initializeComponents();
    }

    /**
     * Add a dependency relationship
     */
    public function addDependency(string $module, string $dependency, string $version): void
    {
        $graph = $this->graphBuilder->getGraph();
        
        if (!isset($graph[$module])) {
            $graph[$module] = ['dependencies' => [], 'dependents' => []];
        }
        
        $graph[$module]['dependencies'][$dependency] = $version;
        
        if (!isset($graph[$dependency])) {
            $graph[$dependency] = ['dependencies' => [], 'dependents' => []];
        }
        
        $graph[$dependency]['dependents'][] = $module;
        
        // Update all components with new graph
        $this->updateComponents($graph);
    }

    /**
     * Build dependency graph from modules
     */
    public function buildDependencyGraph(array $modules): ValidationResult
    {
        $result = $this->graphBuilder->buildGraph($modules);
        
        if (!$result->isValid()) {
            return $result;
        }

        $graph = $this->graphBuilder->getGraph();
        $this->updateComponents($graph);

        // Validate no circular dependencies
        if (!$this->config['allow_circular_dependencies']) {
            $cycles = $this->circularDetector->detectCircularDependencies();
            if (!empty($cycles)) {
                return ValidationResultFactory::failure($cycles);
            }
        }

        // Validate version constraints
        return $this->versionValidator->validateConstraints($graph);
    }

    /**
     * Resolve load order
     */
    public function resolveLoadOrder(): array
    {
        return $this->sorter->sort();
    }

    /**
     * Get parallel load groups
     */
    public function getParallelLoadGroups(): array
    {
        return $this->sorter->getParallelGroups();
    }

    /**
     * Check for circular dependencies
     */
    public function hasCircularDependencies(array $modules = []): bool
    {
        if (!empty($modules)) {
            // Build temporary graph for specific modules
            $tempGraph = [];
            foreach ($modules as $module) {
                if ($this->graphBuilder->hasModule($module)) {
                    $tempGraph[$module] = [
                        'dependencies' => array_flip($this->graphBuilder->getModuleDependencies($module)),
                        'dependents' => $this->graphBuilder->getModuleDependents($module)
                    ];
                }
            }
            
            $tempDetector = new CircularDetector($this->logger, $tempGraph);
            return $tempDetector->hasCircularDependencies();
        }

        return $this->circularDetector->hasCircularDependencies();
    }

    /**
     * Get dependency graph
     */
    public function getDependencyGraph(): array
    {
        return $this->graphBuilder->getGraph();
    }

    /**
     * Get dependencies (alias for compatibility)
     */
    public function getDependencies(): array
    {
        return $this->getDependencyGraph();
    }

    /**
     * Get module dependencies
     */
    public function getModuleDependencies(string $moduleName): array
    {
        return $this->graphBuilder->getModuleDependencies($moduleName);
    }

    /**
     * Get module dependents
     */
    public function getModuleDependents(string $moduleName): array
    {
        return $this->graphBuilder->getModuleDependents($moduleName);
    }

    /**
     * Initialize specialized components
     */
    private function initializeComponents(): void
    {
        $this->graphBuilder = new GraphBuilder($this->logger, $this->config);
        $this->circularDetector = new CircularDetector($this->logger);
        $this->sorter = new TopologicalSorter($this->logger);
        $this->versionValidator = new VersionValidator($this->logger, $this->config);
    }

    /**
     * Update all components with new graph
     */
    private function updateComponents(array $graph): void
    {
        $this->circularDetector->setGraph($graph);
        $this->sorter->setGraph($graph);
        $this->versionValidator->setGraph($graph);
    }
}
