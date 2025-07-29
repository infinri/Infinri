<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ThresholdValidator;
use Infinri\SwarmFramework\Core\Common\StatisticsCalculator;
use Infinri\SwarmFramework\Core\Dependency\DependencyResolver;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Module Registry - Refactored with Centralized Utilities
 * 
 * Manages module registration, discovery, and lifecycle using
 * centralized utilities for validation, logging, and configuration.
 * 
 * BEFORE: 448 lines with massive redundancy
 * AFTER: ~200 lines leveraging centralized utilities
 */
#[Injectable(dependencies: ['LoggerInterface', 'SemanticMeshInterface', 'ThresholdValidator'])]
final class ModuleRegistry
{
    use LoggerTrait;

    private SemanticMeshInterface $mesh;
    private ThresholdValidator $thresholdValidator;
    private ModuleDiscovery $discovery;
    private ModuleValidator $validator;
    private HotSwapManager $hotSwapManager;
    private DependencyResolver $dependencyResolver;
    
    private array $registeredModules = [];
    private array $unitRegistry = [];
    private array $config;
    private bool $initialized = false;

    public function __construct(
        LoggerInterface $logger,
        SemanticMeshInterface $mesh,
        ThresholdValidator $thresholdValidator,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->mesh = $mesh;
        $this->thresholdValidator = $thresholdValidator;
        $this->config = ConfigManager::getConfig('ModuleRegistry', $config);
        
        $this->initializeComponents();
    }

    /**
     * Initialize specialized components using centralized configuration
     */
    private function initializeComponents(): void
    {
        $this->discovery = new ModuleDiscovery($this->config);
        
        // Ensure ThresholdValidator is properly typed
        /** @var ThresholdValidator $thresholdValidator */
        $thresholdValidator = $this->thresholdValidator;
        $this->validator = new ModuleValidator($this->logger, $thresholdValidator, $this->config);
        $this->dependencyResolver = new DependencyResolver($this->logger, $this->config);
        
        // Create SwapOrchestrator dependencies with centralized configuration
        $swapBackupManager = new SwapBackupManager($this->mesh, $this->logger, $this->config);
        $unitLifecycleManager = new UnitLifecycleManager($this->mesh, $this->logger, $this->config);
        $swapValidator = new SwapValidator($this->logger, $this->validator, $this->config);
        $swapOrchestrator = new SwapOrchestrator(
            $this->mesh,
            $this->logger,
            $swapValidator,
            $swapBackupManager,
            $unitLifecycleManager,
            $this->config
        );
        $this->hotSwapManager = new HotSwapManager($this->logger, $swapOrchestrator);
    }

    /**
     * Initialize the module registry with centralized performance timing
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $timer = PerformanceTimer::start('registry_initialization');
        
        try {
            $this->logOperationStart('initialize_registry');
            
            if ($this->config['auto_discover']) {
                $this->discoverModules();
            }
            
            $this->initialized = true;
            $duration = PerformanceTimer::stop('registry_initialization');
            
            $this->logOperationComplete('initialize_registry', [
                'module_count' => count($this->registeredModules),
                'unit_count' => count($this->unitRegistry),
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
        } catch (\Throwable $e) {
            PerformanceTimer::stop('registry_initialization');
            throw ExceptionFactory::module('ModuleRegistry', 'initialization', $e->getMessage());
        }
    }

    /**
     * Register a module with centralized validation and threshold checking
     */
    public function registerModule(string $modulePath): bool
    {
        $timer = PerformanceTimer::start('module_registration');
        
        try {
            // Use centralized threshold validation for module limits
            $currentCount = count($this->registeredModules);
            $limitCheck = $this->thresholdValidator->validateThreshold(
                'ModuleRegistry',
                'module_count',
                (float)$currentCount,
                (float)$this->config['max_modules'],
                '>='
            );
            
            if ($limitCheck['violated']) {
                throw ExceptionFactory::module($modulePath, 'registration', 'Maximum modules limit reached');
            }

            // Load and validate module manifest
            $manifest = $this->loadModuleManifest($modulePath);
            
            if ($this->config['validate_on_register']) {
                $validationResult = $this->validator->validateModule($modulePath, $this->registeredModules);
                if (!$validationResult->isValid()) {
                    throw ExceptionFactory::validation('ModuleRegistry', $validationResult->getErrors());
                }
            }

            // Check dependencies by building dependency graph
            $dependencyResult = $this->dependencyResolver->buildDependencyGraph([$manifest->getName() => $manifest]);
            if (!$dependencyResult->isValid()) {
                throw ExceptionFactory::dependency('ModuleRegistry', $manifest->getName(), 'Dependency resolution failed');
            }

            // Register the module
            $moduleId = $manifest->getName();
            $this->registeredModules[$moduleId] = [
                'manifest' => $manifest,
                'path' => $modulePath,
                'registered_at' => PerformanceTimer::now(),
                'status' => 'active',
                'units' => []
            ];

            // Register units from this module
            $this->registerModuleUnits($moduleId, $manifest);

            $duration = PerformanceTimer::stop('module_registration');
            
            $this->logOperationComplete('register_module', [
                'module_id' => $moduleId,
                'module_name' => $manifest->getName(),
                'path' => $modulePath,
                'unit_count' => count($manifest->getUnits()),
                'duration_ms' => round($duration * 1000, 2)
            ]);

            return true;

        } catch (\Throwable $e) {
            PerformanceTimer::stop('module_registration');
            
            $this->logOperationFailure('register_module', [
                'path' => $modulePath,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Unregister a module with centralized error handling
     */
    public function unregisterModule(string $moduleId): bool
    {
        $timer = PerformanceTimer::start('module_unregistration');
        
        try {
            if (!isset($this->registeredModules[$moduleId])) {
                return false;
            }

            $module = $this->registeredModules[$moduleId];
            
            // Unregister all units from this module
            foreach ($module['units'] as $unitId) {
                unset($this->unitRegistry[$unitId]);
            }

            // Remove module
            unset($this->registeredModules[$moduleId]);

            $duration = PerformanceTimer::stop('module_unregistration');
            
            $this->logOperationComplete('unregister_module', [
                'module_id' => $moduleId,
                'unit_count' => count($module['units']),
                'duration_ms' => round($duration * 1000, 2)
            ]);

            return true;

        } catch (\Throwable $e) {
            PerformanceTimer::stop('module_unregistration');
            
            $this->logOperationFailure('unregister_module', [
                'module_id' => $moduleId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get module information
     */
    public function getModule(string $moduleId): ?array
    {
        return $this->registeredModules[$moduleId] ?? null;
    }

    /**
     * Get all registered modules
     */
    public function getModules(): array
    {
        return $this->registeredModules;
    }

    /**
     * Get unit information
     */
    public function getUnit(string $unitId): ?array
    {
        return $this->unitRegistry[$unitId] ?? null;
    }

    /**
     * Get all registered units
     */
    public function getUnits(): array
    {
        return $this->unitRegistry;
    }

    /**
     * Get registry statistics using centralized statistics calculation
     */
    public function getRegistryStats(): array
    {
        $moduleAges = [];
        $unitCounts = [];
        
        foreach ($this->registeredModules as $module) {
            $moduleAges[] = PerformanceTimer::now() - $module['registered_at'];
            $unitCounts[] = count($module['units']);
        }

        return StatisticsCalculator::buildStatsArray([
            'total_modules' => count($this->registeredModules),
            'total_units' => count($this->unitRegistry),
            'avg_units_per_module' => count($unitCounts) > 0 ? array_sum($unitCounts) / count($unitCounts) : 0,
            'oldest_module_age' => !empty($moduleAges) ? max($moduleAges) : 0,
            'newest_module_age' => !empty($moduleAges) ? min($moduleAges) : 0,
            'initialized' => $this->initialized
        ]);
    }

    /**
     * Hot swap a module using centralized hot swap manager
     */
    public function hotSwapModule(string $moduleId, string $newModulePath): bool
    {
        $timer = PerformanceTimer::start('hot_swap');
        
        try {
            if (!$this->config['enable_hot_swap']) {
                throw ExceptionFactory::swapOperation($moduleId, 'hot_swap', 'Hot swap is disabled');
            }

            $result = $this->hotSwapManager->swapModule($moduleId, $newModulePath, $this->registeredModules);
            $duration = PerformanceTimer::stop('hot_swap');
            
            $this->logOperationComplete('hot_swap', [
                'module_id' => $moduleId,
                'new_path' => $newModulePath,
                'success' => $result->isValid(),
                'duration_ms' => round($duration * 1000, 2)
            ]);

            return $result->isValid();

        } catch (\Throwable $e) {
            PerformanceTimer::stop('hot_swap');
            throw ExceptionFactory::swapOperation($moduleId, 'hot_swap', $e->getMessage());
        }
    }

    /**
     * Discover modules using centralized discovery
     */
    private function discoverModules(): void
    {
        $timer = PerformanceTimer::start('module_discovery');
        
        try {
            $discoveredModules = $this->discovery->discoverAllModules();
            $registeredCount = 0;

            foreach ($discoveredModules as $modulePath) {
                try {
                    if ($this->registerModule($modulePath)) {
                        $registeredCount++;
                    }
                } catch (\Throwable $e) {
                    $this->logOperationFailure('discover_register_module', [
                        'path' => $modulePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $duration = PerformanceTimer::stop('module_discovery');
            
            $this->logOperationComplete('discover_modules', [
                'discovered_count' => count($discoveredModules),
                'registered_count' => $registeredCount,
                'duration_ms' => round($duration * 1000, 2)
            ]);

        } catch (\Throwable $e) {
            PerformanceTimer::stop('module_discovery');
            throw ExceptionFactory::module('ModuleRegistry', 'discovery', $e->getMessage());
        }
    }

    /**
     * Load module manifest with centralized error handling
     */
    private function loadModuleManifest(string $modulePath): ModuleManifest
    {
        try {
            $manifestPath = rtrim($modulePath, '/') . '/swarm-module.json';
            
            if (!file_exists($manifestPath)) {
                throw ExceptionFactory::module($modulePath, 'load_manifest', 'Manifest file not found');
            }

            return ModuleManifest::fromJsonFile($manifestPath);

        } catch (\Throwable $e) {
            throw ExceptionFactory::module($modulePath, 'load_manifest', $e->getMessage());
        }
    }

    /**
     * Register units from a module
     */
    private function registerModuleUnits(string $moduleId, ModuleManifest $manifest): void
    {
        $units = $manifest->getUnits();
        
        foreach ($units as $unitConfig) {
            $unitId = $unitConfig['id'] ?? $unitConfig['class'];
            
            $this->unitRegistry[$unitId] = [
                'module_id' => $moduleId,
                'class' => $unitConfig['class'],
                'config' => $unitConfig,
                'registered_at' => PerformanceTimer::now()
            ];
            
            $this->registeredModules[$moduleId]['units'][] = $unitId;
        }
    }
}
