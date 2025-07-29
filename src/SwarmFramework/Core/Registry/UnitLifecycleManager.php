<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit Lifecycle Manager - Unit startup/shutdown operations
 * 
 * Focused solely on managing unit lifecycle during hot-swap operations.
 * Extracted from monolithic HotSwapManager for modularity.
 */
#[Injectable(dependencies: ['SemanticMeshInterface', 'LoggerInterface'])]
final class UnitLifecycleManager
{
    use LoggerTrait;

    private SemanticMeshInterface $mesh;
    private array $config;

    public function __construct(SemanticMeshInterface $mesh, LoggerInterface $logger, array $config = [])
    {
        $this->mesh = $mesh;
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('UnitLifecycleManager', $config);
    }

    public function gracefulShutdown(array $unitClasses): void
    {
        $startTime = PerformanceTimer::now();
        $context = PerformanceTimer::startWithContext('graceful_shutdown', ['units' => count($unitClasses)]);
        $this->logOperationStart('graceful_shutdown', $context);

        try {
            $unitIds = [];
            
            // Get unit IDs for the classes
            foreach ($unitClasses as $unitClass) {
                $ids = $this->getUnitIdsByClass($unitClass);
                $unitIds = array_merge($unitIds, $ids);
            }

            if (empty($unitIds)) {
                $finalContext = PerformanceTimer::stopWithContext('graceful_shutdown', $context);
                $this->logOperationComplete('graceful_shutdown', $this->buildOperationContext('graceful_shutdown', array_merge($finalContext, ['units_found' => 0])));
                return;
            }

            // Request graceful shutdown
            foreach ($unitIds as $unitId) {
                $this->mesh->set("unit:{$unitId}:shutdown_requested", time());
                $this->mesh->set("unit:{$unitId}:active", false);
            }

            // Wait for units to shutdown gracefully
            $this->waitForShutdown($unitIds);

            $finalContext = PerformanceTimer::stopWithContext('graceful_shutdown', $context);
            $this->logOperationComplete('graceful_shutdown', $this->buildOperationContext('graceful_shutdown', array_merge($finalContext, [
                'units_shutdown' => count($unitIds)
            ])));

        } catch (\Throwable $e) {
            $this->logOperationFailure('graceful_shutdown', $this->buildErrorContext('graceful_shutdown', $e, [
                'trace' => $e->getTraceAsString()
            ]));
        }
    }

    public function initializeUnits(ModuleManifest $manifest): void
    {
        $startTime = PerformanceTimer::now();
        $context = PerformanceTimer::startWithContext('initialize_units', ['module' => $manifest->getName()]);
        $this->logOperationStart('initialize_units', $context);

        try {
            $initializedCount = 0;

            foreach ($manifest->getUnits() as $unitClass) {
                if (class_exists($unitClass)) {
                    $unitId = $this->generateUnitId($unitClass);
                    
                    // Mark unit as available and active
                    $this->mesh->set("unit:{$unitId}:class", $unitClass);
                    $this->mesh->set("unit:{$unitId}:module", $manifest->getName());
                    $this->mesh->set("unit:{$unitId}:initialized", time());
                    $this->mesh->set("unit:{$unitId}:active", true);
                    
                    $initializedCount++;
                } else {
                    $this->logger->warning('Unit class not found during initialization', [
                        'class' => $unitClass,
                        'module' => $manifest->getName()
                    ]);
                }
            }

            $this->logOperationComplete('initialize_units', [
                'units_initialized' => $initializedCount,
                'total_units' => count($manifest->getUnits()),
                'duration_ms' => round((PerformanceTimer::now() - $startTime) * 1000, 2)
            ]);

        } catch (\Throwable $e) {
            $this->logOperationFailure('initialize_units', $this->buildErrorContext('initialize_units', $e, [
                'error' => $e->getMessage()
            ]));
        }
    }

    public function restartUnits(array $unitIds): void
    {
        $startTime = PerformanceTimer::now();
        $context = PerformanceTimer::startWithContext('restart_units', ['units' => count($unitIds)]);
        $this->logOperationStart('restart_units', $context);

        try {
            foreach ($unitIds as $unitId) {
                // Clear shutdown flags and reactivate
                $this->mesh->delete("unit:{$unitId}:shutdown_requested");
                $this->mesh->set("unit:{$unitId}:active", true);
                $this->mesh->set("unit:{$unitId}:restarted", time());
            }

            $this->logOperationComplete('restart_units', [
                'units_restarted' => count($unitIds),
                'duration_ms' => round((PerformanceTimer::now() - $startTime) * 1000, 2)
            ]);

        } catch (\Throwable $e) {
            $this->logOperationFailure('restart_units', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getActiveUnitsForModule(string $moduleName): array
    {
        $activeUnits = [];
        
        try {
            $unitModuleData = $this->mesh->snapshot(["unit:*:module"]);
            
            foreach ($unitModuleData as $key => $unitModule) {
                if ($unitModule === $moduleName) {
                    $keyParts = explode(':', $key);
                    if (count($keyParts) >= 2) {
                        $unitId = $keyParts[1];
                        
                        // Check if unit is active
                        $isActive = $this->mesh->get("unit:{$unitId}:active");
                        if ($isActive) {
                            $activeUnits[] = $unitId;
                        }
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get active units for module', [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);
        }

        return $activeUnits;
    }

    public function isUnitResponsive(string $unitClass): bool
    {
        try {
            // Basic responsiveness check - verify class exists and is loadable
            if (!class_exists($unitClass)) {
                return false;
            }

            // In production, this could include more sophisticated health checks
            // such as pinging the unit's health endpoint or checking its status
            return true;

        } catch (\Throwable $e) {
            $this->logger->warning('Unit responsiveness check failed', [
                'class' => $unitClass,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getUnitHealthStatus(): array
    {
        $healthStatus = [
            'total_units' => 0,
            'active_units' => 0,
            'inactive_units' => 0,
            'shutdown_requested' => 0,
            'responsive_units' => 0
        ];

        try {
            $allUnits = $this->mesh->snapshot(["unit:*:class"]);
            $healthStatus['total_units'] = count($allUnits);

            foreach ($allUnits as $key => $unitClass) {
                $keyParts = explode(':', $key);
                if (count($keyParts) >= 2) {
                    $unitId = $keyParts[1];
                    
                    $isActive = $this->mesh->get("unit:{$unitId}:active");
                    $shutdownRequested = $this->mesh->get("unit:{$unitId}:shutdown_requested");
                    
                    if ($isActive) {
                        $healthStatus['active_units']++;
                    } else {
                        $healthStatus['inactive_units']++;
                    }
                    
                    if ($shutdownRequested) {
                        $healthStatus['shutdown_requested']++;
                    }
                    
                    if ($this->isUnitResponsive($unitClass)) {
                        $healthStatus['responsive_units']++;
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get unit health status', [
                'error' => $e->getMessage()
            ]);
        }

        return $healthStatus;
    }

    private function getUnitIdsByClass(string $unitClass): array
    {
        $unitIds = [];
        
        try {
            $allUnits = $this->mesh->snapshot(["unit:*:class"]);
            
            foreach ($allUnits as $key => $storedClass) {
                if ($storedClass === $unitClass) {
                    $keyParts = explode(':', $key);
                    if (count($keyParts) >= 2) {
                        $unitIds[] = $keyParts[1];
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get unit IDs by class', [
                'class' => $unitClass,
                'error' => $e->getMessage()
            ]);
        }

        return $unitIds;
    }

    private function waitForShutdown(array $unitIds): void
    {
        $timeout = time() + $this->config['graceful_shutdown_timeout'];
        
        while (time() < $timeout) {
            $allShutdown = true;
            
            foreach ($unitIds as $unitId) {
                $isActive = $this->mesh->get("unit:{$unitId}:active");
                if ($isActive) {
                    $allShutdown = false;
                    break;
                }
            }
            
            if ($allShutdown) {
                return;
            }
            
            sleep($this->config['health_check_interval']);
        }

        // Force shutdown if graceful timeout exceeded
        $this->logger->warning('Graceful shutdown timeout exceeded, forcing shutdown', [
            'timeout' => $this->config['graceful_shutdown_timeout'],
            'remaining_units' => count(array_filter($unitIds, fn($id) => $this->mesh->get("unit:{$id}:active")))
        ]);

        foreach ($unitIds as $unitId) {
            $this->mesh->set("unit:{$unitId}:active", false);
            $this->mesh->set("unit:{$unitId}:force_shutdown", time());
        }
    }

    private function generateUnitId(string $unitClass): string
    {
        return PerformanceTimer::generateHashId($unitClass);
    }
}
