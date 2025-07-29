<?php declare(strict_types=1);

namespace Infinri\SwarmFramework\Core\Registry;

use Infinri\SwarmFramework\Core\Common\LoggerTrait;
use Infinri\SwarmFramework\Core\Common\ConfigManager;
use Infinri\SwarmFramework\Core\Common\PerformanceTimer;
use Infinri\SwarmFramework\Core\Common\ExceptionFactory;
use Infinri\SwarmFramework\Core\Attributes\Injectable;
use Infinri\SwarmFramework\Core\Mesh\ModuleManifest;
use Infinri\SwarmFramework\Interfaces\SemanticMeshInterface;
use Psr\Log\LoggerInterface;

/**
 * Swap Backup Manager - Backup and restore operations
 * 
 * Focused solely on creating and managing backups during hot-swap operations.
 * Extracted from monolithic HotSwapManager for modularity.
 */
#[Injectable(dependencies: ['SemanticMeshInterface', 'LoggerInterface'])]
final class SwapBackupManager
{
    use LoggerTrait;

    private SemanticMeshInterface $mesh;
    private array $config;

    public function __construct(SemanticMeshInterface $mesh, LoggerInterface $logger, array $config = [])
    {
        $this->mesh = $mesh;
        $this->logger = $logger;
        $this->config = ConfigManager::getConfig('SwapBackupManager', $config);
    }

    public function createBackup(string $moduleName, ?ModuleManifest $oldModule): array
    {
        $context = PerformanceTimer::startWithContext('create_backup', ['module' => $moduleName]);
        $this->logOperationStart('create_backup', ['module' => $moduleName]);

        try {
            $backupData = [
                'module_name' => $moduleName,
                'timestamp' => time(),
                'mesh_snapshot' => [],
                'temp_files' => []
            ];

            // Create mesh snapshot for rollback
            $backupData['mesh_snapshot'] = $this->createMeshSnapshot($moduleName);

            // Backup old module file if it exists
            if ($oldModule && $this->config['backup_old_modules']) {
                $backupData['old_module_backup'] = $this->backupModuleFile($oldModule);
            }

            // Store backup metadata in mesh
            $this->mesh->set("backup:{$moduleName}:metadata", serialize($backupData));

            $this->logOperationComplete('create_backup', [
                'snapshot_keys' => count($backupData['mesh_snapshot']),
                'temp_files' => count($backupData['temp_files']),
                'duration_ms' => round($context->getDuration() * 1000, 2)
            ]);

            return $backupData;

        } catch (\Throwable $e) {
            $this->logOperationFailure('create_backup', [
                'error' => $e->getMessage(),
                'trace' => ExceptionFactory::getTraceAsString($e)
            ]);
            return [];
        }
    }

    public function restoreBackup(string $moduleName, array $backupData): void
    {
        $context = PerformanceTimer::startWithContext('restore_backup', ['module' => $moduleName]);
        $this->logOperationStart('restore_backup', ['module' => $moduleName]);

        try {
            // Restore mesh snapshot
            if (!empty($backupData['mesh_snapshot'])) {
                $this->restoreMeshSnapshot($moduleName, $backupData['mesh_snapshot']);
            }

            // Restore old module file if backed up
            if (isset($backupData['old_module_backup'])) {
                $this->restoreModuleFile($backupData['old_module_backup']);
            }

            // Clean up backup metadata
            $this->mesh->delete("backup:{$moduleName}:metadata");

            $this->logOperationComplete('restore_backup', [
                'duration_ms' => round($context->getDuration() * 1000, 2)
            ]);

        } catch (\Throwable $e) {
            $this->logOperationFailure('restore_module', [
                'error' => $e->getMessage(),
                'trace' => ExceptionFactory::getTraceAsString($e)
            ]);
        }
    }

    public function cleanupBackup(array $backupData): void
    {
        $context = PerformanceTimer::startWithContext('cleanup_backup');
        $this->logOperationStart('cleanup_backup');

        try {
            // Clean up temporary files
            if (isset($backupData['temp_files'])) {
                foreach ($backupData['temp_files'] as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }

            // Clean up backup metadata
            if (isset($backupData['module_name'])) {
                $this->mesh->delete("backup:{$backupData['module_name']}:metadata");
            }

            $this->logOperationComplete('backup_module', [
                'module' => $backupData['module_name'],
                'backup_path' => $backupData['backup_path'] ?? '',
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

        } catch (\Throwable $e) {
            $this->logOperationFailure('backup_module', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function cleanupOldBackups(): int
    {
        $cleaned = 0;
        $cutoffTime = time() - $this->config['max_backup_age'];

        try {
            // Get all backup metadata keys
            $backupKeys = $this->mesh->snapshot(['backup:*:metadata']);
            
            foreach ($backupKeys as $key => $serializedData) {
                $backupData = unserialize($serializedData);
                
                if (isset($backupData['timestamp']) && $backupData['timestamp'] < $cutoffTime) {
                    $this->cleanupBackup($backupData);
                    $cleaned++;
                }
            }

            $this->logger->info('Cleaned up old backups', ['count' => $cleaned]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to cleanup old backups', ['error' => $e->getMessage()]);
        }

        return $cleaned;
    }

    private function createMeshSnapshot(string $moduleName): array
    {
        $keyPattern = "module:{$moduleName}:*";
        $snapshot = $this->mesh->snapshot([$keyPattern]);
        
        // Also capture unit data for this module
        $unitModuleData = $this->mesh->snapshot(["unit:*:module"]);
        foreach ($unitModuleData as $key => $unitModule) {
            if ($unitModule === $moduleName) {
                $keyParts = explode(':', $key);
                if (count($keyParts) >= 2) {
                    $unitId = $keyParts[1];
                    // Capture all unit data
                    $unitData = $this->mesh->snapshot(["unit:{$unitId}:*"]);
                    $snapshot = array_merge($snapshot, $unitData);
                }
            }
        }

        return $snapshot;
    }

    private function restoreMeshSnapshot(string $moduleName, array $snapshot): void
    {
        // Clear current module data
        $currentKeys = $this->mesh->snapshot(["module:{$moduleName}:*"]);
        foreach (array_keys($currentKeys) as $key) {
            $this->mesh->delete($key);
        }

        // Restore snapshot data
        foreach ($snapshot as $key => $value) {
            $this->mesh->set($key, $value);
        }
    }

    private function backupModuleFile(ModuleManifest $module): array
    {
        // Derive module file path from module name and base modules directory
        $moduleName = $module->getName();
        $originalPath = $this->getModuleFilePath($moduleName);
        $backupPath = $this->generateBackupPath($originalPath);
        
        // Ensure backup directory exists
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Copy module file to backup location
        if (copy($originalPath, $backupPath)) {
            return [
                'original_path' => $originalPath,
                'backup_path' => $backupPath,
                'backup_time' => time()
            ];
        }

        throw ExceptionFactory::createFileOperationException(
            "Failed to backup module file: {$originalPath}",
            ['original_path' => $originalPath, 'backup_dir' => $this->config['backup_directory']]
        );
    }

    private function restoreModuleFile(array $backupInfo): void
    {
        if (!isset($backupInfo['backup_path']) || !isset($backupInfo['original_path'])) {
            throw ExceptionFactory::createValidationException(
                'Invalid backup information for module file restore',
                ['backup_info' => $backupInfo]
            );
        }

        if (!file_exists($backupInfo['backup_path'])) {
            throw ExceptionFactory::createFileOperationException(
                "Backup file not found: {$backupInfo['backup_path']}",
                ['backup_path' => $backupInfo['backup_path']]
            );
        }

        if (!copy($backupInfo['backup_path'], $backupInfo['original_path'])) {
            throw ExceptionFactory::createFileOperationException(
                "Failed to restore module file: {$backupInfo['original_path']}",
                ['original_path' => $backupInfo['original_path'], 'backup_path' => $backupInfo['backup_path']]
            );
        }

        // Clean up backup file
        unlink($backupInfo['backup_path']);
    }

    private function generateBackupPath(string $originalPath): string
    {
        $filename = basename($originalPath);
        $timestamp = date('Y-m-d_H-i-s');
        $backupFilename = "{$timestamp}_{$filename}";
        
        return $this->config['backup_directory'] . '/' . $backupFilename;
    }

    /**
     * Get the file path for a module based on its name
     */
    private function getModuleFilePath(string $moduleName): string
    {
        // Default modules directory - this should be configurable
        $modulesDirectory = $this->config['modules_directory'] ?? '/var/swarm/modules';
        
        // Convert module name to file path (e.g., 'my-module' -> 'my-module.php')
        $moduleFileName = $moduleName . '.php';
        
        return $modulesDirectory . '/' . $moduleFileName;
    }
}
