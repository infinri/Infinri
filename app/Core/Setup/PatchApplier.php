<?php

declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Core\Setup;

use App\Core\Contracts\Database\ConnectionInterface;
use App\Core\Setup\Patch\DataPatchInterface;
use App\Core\Setup\Patch\SchemaPatchInterface;
use App\Core\Module\ModuleRegistry;

/**
 * Patch Applier
 * 
 * Discovers and applies pending patches from all enabled modules.
 * Handles dependency resolution and execution order.
 */
class PatchApplier
{
    protected ConnectionInterface $connection;
    protected PatchRegistry $registry;
    protected ModuleRegistry $modules;
    protected array $pendingPatches = [];
    protected array $appliedInRun = [];

    public function __construct(
        ConnectionInterface $connection,
        PatchRegistry $registry,
        ModuleRegistry $modules
    ) {
        $this->connection = $connection;
        $this->registry = $registry;
        $this->modules = $modules;
    }

    /**
     * Apply all pending patches
     * 
     * @return array Applied patches info
     */
    public function applyAll(): array
    {
        $results = [
            'schema' => [],
            'data' => [],
            'errors' => [],
        ];

        // Apply schema patches first
        $schemaPatches = $this->discoverPatches(SchemaPatchInterface::class);
        foreach ($this->resolveDependencies($schemaPatches) as $patchInfo) {
            try {
                $this->applyPatch($patchInfo, 'schema');
                $results['schema'][] = $patchInfo['class'];
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'patch' => $patchInfo['class'],
                    'error' => $e->getMessage(),
                ];
                throw $e; // Stop on error
            }
        }

        // Then apply data patches
        $dataPatches = $this->discoverPatches(DataPatchInterface::class);
        foreach ($this->resolveDependencies($dataPatches) as $patchInfo) {
            try {
                $this->applyPatch($patchInfo, 'data');
                $results['data'][] = $patchInfo['class'];
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'patch' => $patchInfo['class'],
                    'error' => $e->getMessage(),
                ];
                throw $e; // Stop on error
            }
        }

        return $results;
    }

    /**
     * Get pending patches without applying them
     */
    public function getPending(): array
    {
        return [
            'schema' => $this->discoverPatches(SchemaPatchInterface::class),
            'data' => $this->discoverPatches(DataPatchInterface::class),
        ];
    }

    /**
     * Discover patches of a specific type from all modules
     */
    protected function discoverPatches(string $interface): array
    {
        $patches = [];

        foreach ($this->modules->getEnabled() as $module) {
            $patchDir = $module->path . '/Setup/Data';
            
            if ($interface === SchemaPatchInterface::class) {
                $patchDir = $module->path . '/Setup/Schema';
            }

            if (!is_dir($patchDir)) {
                continue;
            }

            foreach (glob($patchDir . '/*.php') as $file) {
                $className = $this->getClassNameFromFile($file, $module->name);
                
                if ($className === null) {
                    continue;
                }

                require_once $file;

                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                
                if (!$reflection->implementsInterface($interface)) {
                    continue;
                }

                if ($reflection->isAbstract()) {
                    continue;
                }

                // Check if already applied
                if ($this->registry->isApplied($className)) {
                    continue;
                }

                // Check aliases
                $instance = $reflection->newInstance($this->connection);
                if ($this->registry->isAliasApplied($instance->getAliases())) {
                    continue;
                }

                $patches[] = [
                    'class' => $className,
                    'module' => $module->name,
                    'file' => $file,
                    'dependencies' => $className::getDependencies(),
                ];
            }
        }

        return $patches;
    }

    /**
     * Resolve patch dependencies and return execution order
     */
    protected function resolveDependencies(array $patches): array
    {
        $resolved = [];
        $seen = [];

        $resolve = function (array $patch) use (&$resolve, &$resolved, &$seen, $patches) {
            if (isset($seen[$patch['class']])) {
                return;
            }
            $seen[$patch['class']] = true;

            foreach ($patch['dependencies'] as $dep) {
                // Find dependency in patches
                foreach ($patches as $p) {
                    if ($p['class'] === $dep) {
                        $resolve($p);
                        break;
                    }
                }
            }

            $resolved[] = $patch;
        };

        foreach ($patches as $patch) {
            $resolve($patch);
        }

        return $resolved;
    }

    /**
     * Apply a single patch
     */
    protected function applyPatch(array $patchInfo, string $type): void
    {
        $className = $patchInfo['class'];
        $module = $patchInfo['module'];

        $instance = new $className($this->connection);

        $this->connection->transaction(function () use ($instance, $className, $type, $module) {
            $instance->apply();
            $this->registry->markApplied($className, $type, $module);
        });

        $this->appliedInRun[] = $className;
    }

    /**
     * Get class name from file path
     */
    protected function getClassNameFromFile(string $file, string $moduleName): ?string
    {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $relativePath = str_replace(base_path('app/Modules/' . $moduleName . '/'), '', $file);
        $relativePath = str_replace('.php', '', $relativePath);
        $namespace = 'App\\Modules\\' . $moduleName . '\\' . str_replace('/', '\\', $relativePath);
        
        // Extract just the directory structure for namespace
        $parts = explode('/', dirname($relativePath));
        $namespace = 'App\\Modules\\' . $moduleName . '\\' . implode('\\', $parts) . '\\' . $filename;

        return $namespace;
    }
}
