<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ValidationResultFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Infinri\SwarmFramework\Interfaces\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Swap Orchestrator - Coordinates hot-swap operations
 * 
 * Focused solely on orchestrating the swap process phases.
 * Extracted from monolithic HotSwapManager for modularity.
 */
#[Injectable(dependencies: ['SemanticMeshInterface', 'LoggerInterface', 'SwapValidator', 'SwapBackupManager', 'UnitLifecycleManager'])]
final class SwapOrchestrator
{
    use LoggerTrait;

    private SemanticMeshInterface $mesh;
    private SwapValidator $validator;
    private SwapBackupManager $backupManager;
    private UnitLifecycleManager $unitManager;
    private array $config;
    private array $swapHistory = [];

    public function __construct(
        SemanticMeshInterface $mesh,
        LoggerInterface $logger,
        SwapValidator $validator,
        SwapBackupManager $backupManager,
        UnitLifecycleManager $unitManager,
        array $config = []
    ) {
        $this->mesh = $mesh;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->backupManager = $backupManager;
        $this->unitManager = $unitManager;
        $this->config = ConfigManager::getConfig('SwapOrchestrator', $config);
    }

    public function executeSwap(string $moduleName, string $newModulePath, array &$registeredModules): ValidationResult
    {
        $swapId = uniqid('swap_', true);
        PerformanceTimer::start('hot_swap_operation');
        
        $this->logOperationStart('hot_swap_operation', [
            'swap_id' => $swapId,
            'module' => $moduleName,
            'new_path' => $newModulePath
        ]);

        try {
            // Phase 1: Pre-swap validation
            $validationResult = $this->validator->validatePreSwap($newModulePath, $registeredModules);
            if (!$validationResult->isValid()) {
                return $validationResult;
            }

            // Phase 2: Prepare for swap
            $oldModule = $registeredModules[$moduleName] ?? null;
            $backupData = $this->backupManager->createBackup($moduleName, $oldModule);

            // Phase 3: Execute swap
            $swapResult = $this->performSwap($moduleName, $newModulePath, $registeredModules);
            if (!$swapResult->isValid()) {
                $this->backupManager->restoreBackup($moduleName, $backupData);
                return $swapResult;
            }

            // Phase 4: Post-swap verification
            $verificationResult = $this->validator->validatePostSwap($moduleName, $registeredModules);
            if (!$verificationResult->isValid()) {
                $this->backupManager->restoreBackup($moduleName, $backupData);
                return $verificationResult;
            }

            // Phase 5: Finalize swap
            $this->finalizeSwap($swapId, $moduleName, $oldModule, $backupData);

            $duration = PerformanceTimer::stop('hot_swap_operation');
            
            $this->logOperationComplete('hot_swap_operation', [
                'swap_id' => $swapId,
                'module' => $moduleName,
                'success' => true,
                'duration_ms' => round($duration * 1000, 2)
            ]);

            $this->logger->info('Hot swap completed successfully', [
                'swap_id' => $swapId,
                'module' => $moduleName,
                'duration' => round($duration, 3)
            ]);

            return ValidationResult::success(['Hot swap completed successfully']);

        } catch (\Throwable $e) {
            $this->logger->error('Hot swap failed with exception', [
                'swap_id' => $swapId,
                'module' => $moduleName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Attempt rollback on exception
            if (isset($backupData)) {
                $this->backupManager->restoreBackup($moduleName, $backupData);
            }

            $this->recordSwapFailure($swapId, $moduleName, $e->getMessage());
            return ValidationResultFactory::createFailure(['Hot swap failed: ' . $e->getMessage()]);
        }
    }

    public function getSwapHistory(): array
    {
        return $this->swapHistory;
    }

    public function getSwapStats(): array
    {
        $totalSwaps = count($this->swapHistory);
        $successfulSwaps = array_filter($this->swapHistory, fn($swap) => isset($swap['duration']));
        
        return [
            'total_swaps' => $totalSwaps,
            'successful_swaps' => count($successfulSwaps),
            'success_rate' => $totalSwaps > 0 ? count($successfulSwaps) / $totalSwaps : 0,
            'average_duration' => $this->calculateAverageDuration($successfulSwaps),
            'last_swap' => !empty($this->swapHistory) ? max(array_column($this->swapHistory, 'timestamp')) : null
        ];
    }

    public function hasActiveSwaps(): bool
    {
        $activeSwaps = $this->mesh->get('active_swaps') ?? [];
        
        // Clean up expired swaps
        $now = time();
        foreach ($activeSwaps as $swapId => $startTime) {
            if ($now - $startTime > $this->config['max_swap_time']) {
                unset($activeSwaps[$swapId]);
            }
        }
        
        $this->mesh->set('active_swaps', $activeSwaps);
        return !empty($activeSwaps);
    }

    private function performSwap(string $moduleName, string $newModulePath, array &$registeredModules): ValidationResult
    {
        try {
            // Gracefully shutdown old units
            if (isset($registeredModules[$moduleName])) {
                $oldModule = $registeredModules[$moduleName];
                $this->unitManager->gracefulShutdown($oldModule->getUnits());
            }

            // Load new module
            $newManifest = ModuleManifest::fromJsonFile($newModulePath);
            $registeredModules[$moduleName] = $newManifest;

            // Initialize new units
            $this->unitManager->initializeUnits($newManifest);

            // Update mesh registry
            $this->mesh->set("module:{$moduleName}:manifest", serialize($newManifest));
            $this->mesh->set("module:{$moduleName}:path", $newModulePath);
            $this->mesh->set("module:{$moduleName}:swapped_at", time());

            return ValidationResultFactory::createSuccess();

        } catch (\Throwable $e) {
            return ValidationResultFactory::createFailure(["Swap execution failed: {$e->getMessage()}"]);
        }
    }

    private function finalizeSwap(string $swapId, string $moduleName, ?ModuleManifest $oldModule, array $backupData): void
    {
        // Remove from active swaps
        $activeSwaps = $this->mesh->get('active_swaps') ?? [];
        unset($activeSwaps[$swapId]);
        $this->mesh->set('active_swaps', $activeSwaps);

        // Clean up backup data if configured
        if (!$this->config['enable_rollback']) {
            $this->backupManager->cleanupBackup($backupData);
        }

        // Update module metadata
        $this->mesh->set("module:{$moduleName}:last_swap", time());
        $this->mesh->set("module:{$moduleName}:swap_count", 
            ($this->mesh->get("module:{$moduleName}:swap_count") ?? 0) + 1);
    }

    private function recordSwapSuccess(string $swapId, string $moduleName, float $duration): void
    {
        $this->swapHistory[] = [
            'swap_id' => $swapId,
            'module' => $moduleName,
            'timestamp' => time(),
            'duration' => $duration,
            'success' => true
        ];
    }

    private function recordSwapFailure(string $swapId, string $moduleName, string $error): void
    {
        $this->swapHistory[] = [
            'swap_id' => $swapId,
            'module' => $moduleName,
            'timestamp' => time(),
            'success' => false,
            'error' => $error
        ];
    }

    private function calculateAverageDuration(array $successfulSwaps): float
    {
        if (empty($successfulSwaps)) {
            return 0.0;
        }

        $totalDuration = array_sum(array_column($successfulSwaps, 'duration'));
        return $totalDuration / count($successfulSwaps);
    }
}
