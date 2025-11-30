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
use App\Core\Database\Schema\SchemaBuilder;

/**
 * Patch Registry
 * 
 * Tracks which patches have been applied to prevent re-running them.
 * Similar to Magento's patch_list table.
 */
class PatchRegistry
{
    protected ConnectionInterface $connection;
    protected SchemaBuilder $schema;
    protected string $tableName = 'patch_list';
    protected array $appliedPatches = [];
    protected bool $loaded = false;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->schema = new SchemaBuilder($connection);
    }

    /**
     * Ensure the patch_list table exists
     */
    public function ensureTableExists(): void
    {
        if ($this->schema->hasTable($this->tableName)) {
            return;
        }

        $this->schema->create($this->tableName, function ($table) {
            $table->id();
            $table->string('patch_name', 255)->unique();
            $table->string('patch_type', 20); // 'data' or 'schema'
            $table->string('module', 100);
            $table->timestamp('applied_at')->useCurrent();
        });
    }

    /**
     * Check if a patch has been applied
     */
    public function isApplied(string $patchClass): bool
    {
        $this->load();
        return isset($this->appliedPatches[$patchClass]);
    }

    /**
     * Check if any alias of a patch has been applied
     */
    public function isAliasApplied(array $aliases): bool
    {
        $this->load();
        
        foreach ($aliases as $alias) {
            if (isset($this->appliedPatches[$alias])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mark a patch as applied
     */
    public function markApplied(string $patchClass, string $type, string $module): void
    {
        $this->connection->insert(
            "INSERT INTO \"{$this->tableName}\" (patch_name, patch_type, module, applied_at) VALUES (?, ?, ?, ?)",
            [$patchClass, $type, $module, date('Y-m-d H:i:s')]
        );

        $this->appliedPatches[$patchClass] = true;
    }

    /**
     * Get all applied patches
     */
    public function getAppliedPatches(): array
    {
        $this->load();
        return array_keys($this->appliedPatches);
    }

    /**
     * Get applied patches for a specific module
     */
    public function getAppliedPatchesForModule(string $module): array
    {
        $results = $this->connection->select(
            "SELECT patch_name FROM \"{$this->tableName}\" WHERE module = ? ORDER BY applied_at",
            [$module]
        );

        return array_column($results, 'patch_name');
    }

    /**
     * Load applied patches from database
     */
    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->ensureTableExists();

        $results = $this->connection->select(
            "SELECT patch_name FROM \"{$this->tableName}\""
        );

        foreach ($results as $row) {
            $this->appliedPatches[$row['patch_name']] = true;
        }

        $this->loaded = true;
    }

    /**
     * Reset loaded state (for testing)
     */
    public function reset(): void
    {
        $this->appliedPatches = [];
        $this->loaded = false;
    }
}
