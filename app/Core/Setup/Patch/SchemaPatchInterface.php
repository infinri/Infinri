<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Setup\Patch;

/**
 * Schema Patch Interface
 *
 * Schema patches are one-time operations that modify the database schema.
 * Unlike data patches, these run before data operations and are used for:
 * - Adding indexes
 * - Modifying column types
 * - Complex schema changes that can't be done declaratively
 *
 * For simple table creation, use declarative schema (Setup/schema.php) instead.
 * Schema patches are for changes that need procedural logic.
 *
 * @example
 * ```php
 * class AddFullTextIndex implements SchemaPatchInterface
 * {
 *     public function apply(): void
 *     {
 *         $this->connection->statement(
 *             'CREATE INDEX idx_pages_fulltext ON pages USING gin(to_tsvector(\'english\', content))'
 *         );
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return []; // Schema patches that must run first
 *     }
 *
 *     public function getAliases(): array
 *     {
 *         return [];
 *     }
 * }
 * ```
 */
interface SchemaPatchInterface
{
    /**
     * Apply the schema patch
     *
     * This method is called once when the patch is applied.
     */
    public function apply(): void;

    /**
     * Get patches that this patch depends on
     *
     * @return string[] Array of fully-qualified class names
     */
    public static function getDependencies(): array;

    /**
     * Get aliases for this patch
     *
     * @return string[] Array of old class names
     */
    public function getAliases(): array;
}
