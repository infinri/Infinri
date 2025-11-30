<?php

declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Core\Setup\Patch;

/**
 * Data Patch Interface
 * 
 * Data patches are one-time operations that modify data in the database.
 * Unlike schema patches, these run after the schema is set up and are
 * typically used for:
 * - Seeding default data (roles, permissions, settings)
 * - Data migrations (transforming existing data)
 * - One-time data fixes
 * 
 * Each patch runs only once and is tracked in the `patch_list` table.
 * 
 * @example
 * ```php
 * class SeedDefaultRoles implements DataPatchInterface
 * {
 *     public function apply(): void
 *     {
 *         // Insert default roles
 *     }
 *     
 *     public static function getDependencies(): array
 *     {
 *         return []; // Or list other patches that must run first
 *     }
 *     
 *     public function getAliases(): array
 *     {
 *         return []; // Old class names if renamed
 *     }
 * }
 * ```
 */
interface DataPatchInterface
{
    /**
     * Apply the data patch
     * 
     * This method is called once when the patch is applied.
     * It should be idempotent where possible (safe to run multiple times).
     */
    public function apply(): void;

    /**
     * Get patches that this patch depends on
     * 
     * Returns an array of patch class names that must be applied
     * before this patch can run.
     * 
     * @return string[] Array of fully-qualified class names
     */
    public static function getDependencies(): array;

    /**
     * Get aliases for this patch
     * 
     * If this patch was renamed, return the old class names here.
     * This prevents the patch from being applied again under a new name.
     * 
     * @return string[] Array of old class names
     */
    public function getAliases(): array;
}
