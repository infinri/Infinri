<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Setup;

use App\Core\Contracts\Database\ConnectionInterface;
use App\Core\Database\Schema\SchemaBuilder;
use App\Core\Module\ModuleRegistry;
use Throwable;

/**
 * Schema Processor
 *
 * Processes declarative schema.php files from modules and applies
 * them to the database. Handles:
 * - Table creation
 * - Column additions/modifications
 * - Index creation
 * - Foreign key constraints
 * - Dependency ordering between modules
 *
 * Schema files are declarative - you define the desired state,
 * and the processor figures out what changes to apply.
 */
class SchemaProcessor
{
    protected ConnectionInterface $connection;
    protected SchemaBuilder $schema;
    protected ModuleRegistry $modules;
    protected array $processedTables = [];
    protected array $schemaState = [];
    protected string $statePath;

    public function __construct(
        ConnectionInterface $connection,
        ModuleRegistry $modules,
        ?string $statePath = null
    ) {
        $this->connection = $connection;
        $this->schema = new SchemaBuilder($connection);
        $this->modules = $modules;
        $this->statePath = $statePath ?? base_path('var/state/schema.php');
        $this->loadState();
    }

    /**
     * Process all module schemas
     *
     * @return array Results of processing
     */
    public function processAll(): array
    {
        $results = [
            'created' => [],
            'modified' => [],
            'skipped' => [],
            'errors' => [],
        ];

        // Collect all schemas with dependency info
        $schemas = $this->collectSchemas();

        // Sort by dependencies
        $ordered = $this->sortByDependencies($schemas);

        // Process each schema
        foreach ($ordered as $schemaInfo) {
            try {
                $result = $this->processSchema($schemaInfo);

                if ($result['created']) {
                    $results['created'] = array_merge($results['created'], $result['created']);
                }
                if ($result['modified']) {
                    $results['modified'] = array_merge($results['modified'], $result['modified']);
                }
                if ($result['skipped']) {
                    $results['skipped'] = array_merge($results['skipped'], $result['skipped']);
                }
            } catch (Throwable $e) {
                $results['errors'][] = [
                    'module' => $schemaInfo['module'],
                    'error' => $e->getMessage(),
                ];
                throw $e;
            }
        }

        $this->saveState();

        return $results;
    }

    /**
     * Get pending schema changes without applying them
     */
    public function getPending(): array
    {
        $pending = [];
        $schemas = $this->collectSchemas();

        foreach ($schemas as $schemaInfo) {
            $definition = $schemaInfo['definition'];
            $tables = $definition['tables'] ?? [];

            foreach ($tables as $tableName => $tableDefinition) {
                if (! $this->schema->hasTable($tableName)) {
                    $pending[] = [
                        'type' => 'create',
                        'table' => $tableName,
                        'module' => $schemaInfo['module'],
                    ];
                } else {
                    $changes = $this->getTableChanges($tableName, $tableDefinition);
                    if (! empty($changes)) {
                        $pending[] = [
                            'type' => 'modify',
                            'table' => $tableName,
                            'module' => $schemaInfo['module'],
                            'changes' => $changes,
                        ];
                    }
                }
            }
        }

        return $pending;
    }

    /**
     * Collect schemas from all enabled modules
     */
    protected function collectSchemas(): array
    {
        $schemas = [];

        foreach ($this->modules->getEnabled() as $module) {
            $schemaFile = $module->path . '/Setup/schema.php';

            if (! file_exists($schemaFile)) {
                continue;
            }

            $definition = require $schemaFile;

            if (! is_array($definition)) {
                continue;
            }

            $schemas[] = [
                'module' => $module->name,
                'path' => $schemaFile,
                'definition' => $definition,
                'dependencies' => $definition['dependencies'] ?? [],
            ];
        }

        return $schemas;
    }

    /**
     * Sort schemas by dependencies
     */
    protected function sortByDependencies(array $schemas): array
    {
        $sorted = [];
        $seen = [];

        $visit = function (array $schema) use (&$visit, &$sorted, &$seen, $schemas): void {
            $name = $schema['module'];

            if (isset($seen[$name])) {
                return;
            }
            $seen[$name] = true;

            // Process dependencies first (tables from other modules)
            foreach ($schema['dependencies'] as $depTable => $depInfo) {
                // Find which module owns this table
                foreach ($schemas as $s) {
                    $tables = $s['definition']['tables'] ?? [];
                    if (isset($tables[$depTable])) {
                        $visit($s);
                        break;
                    }
                }
            }

            $sorted[] = $schema;
        };

        foreach ($schemas as $schema) {
            $visit($schema);
        }

        return $sorted;
    }

    /**
     * Process a single module's schema
     */
    protected function processSchema(array $schemaInfo): array
    {
        $result = [
            'created' => [],
            'modified' => [],
            'skipped' => [],
        ];

        $definition = $schemaInfo['definition'];
        $module = $schemaInfo['module'];
        $tables = $definition['tables'] ?? [];

        foreach ($tables as $tableName => $tableDefinition) {
            if (! $this->schema->hasTable($tableName)) {
                $this->createTable($tableName, $tableDefinition);
                $result['created'][] = $tableName;
                $this->markTableProcessed($tableName, $module);
            } else {
                $changes = $this->applyTableChanges($tableName, $tableDefinition);
                if (! empty($changes)) {
                    $result['modified'][] = $tableName;
                } else {
                    $result['skipped'][] = $tableName;
                }
            }
        }

        return $result;
    }

    /**
     * Create a table from definition
     */
    protected function createTable(string $tableName, array $definition): void
    {
        $this->schema->create($tableName, function ($table) use ($definition): void {
            $columns = $definition['columns'] ?? [];

            foreach ($columns as $columnName => $columnDef) {
                $this->addColumn($table, $columnName, $columnDef);
            }

            // Add indexes
            $indexes = $definition['indexes'] ?? [];
            foreach ($indexes as $indexName => $indexDef) {
                $this->addIndex($table, $indexName, $indexDef);
            }
        });

        // Add foreign keys after table creation
        $foreignKeys = $definition['foreign_keys'] ?? [];
        foreach ($foreignKeys as $fkName => $fkDef) {
            $this->addForeignKey($tableName, $fkName, $fkDef);
        }
    }

    /**
     * Add a column to table builder
     */
    protected function addColumn($table, string $name, array $definition): void
    {
        $type = $definition['type'] ?? 'string';
        $column = null;

        switch ($type) {
            case 'id':
                $column = $table->id($name);
                break;
            case 'bigIncrements':
                $column = $table->bigIncrements($name);
                break;
            case 'string':
                $length = $definition['length'] ?? 255;
                $column = $table->string($name, $length);
                break;
            case 'text':
                $column = $table->text($name);
                break;
            case 'integer':
                $column = $table->integer($name);
                break;
            case 'bigInteger':
                $column = $table->bigInteger($name);
                break;
            case 'boolean':
                $column = $table->boolean($name);
                break;
            case 'timestamp':
                $column = $table->timestamp($name);
                break;
            case 'timestamps':
                $table->timestamps();

                return;
            case 'foreignId':
                $column = $table->foreignId($name);
                break;
            case 'json':
                $column = $table->json($name);
                break;
            case 'decimal':
                $precision = $definition['precision'] ?? 8;
                $scale = $definition['scale'] ?? 2;
                $column = $table->decimal($name, $precision, $scale);
                break;
            case 'date':
                $column = $table->date($name);
                break;
            case 'datetime':
                $column = $table->datetime($name);
                break;
            default:
                $column = $table->string($name);
        }

        if ($column === null) {
            return;
        }

        // Apply modifiers
        if ($definition['nullable'] ?? false) {
            $column->nullable();
        }
        if (isset($definition['default'])) {
            $column->default($definition['default']);
        }
        if ($definition['unique'] ?? false) {
            $column->unique();
        }
        if ($definition['useCurrent'] ?? false) {
            $column->useCurrent();
        }
    }

    /**
     * Add an index
     */
    protected function addIndex($table, string $name, array $definition): void
    {
        $columns = $definition['columns'] ?? [$name];
        $type = $definition['type'] ?? 'index';

        switch ($type) {
            case 'unique':
                $table->unique($columns, $name);
                break;
            case 'index':
            default:
                $table->index($columns, $name);
                break;
        }
    }

    /**
     * Add a foreign key
     */
    protected function addForeignKey(string $tableName, string $name, array $definition): void
    {
        $column = $definition['column'];
        $references = $definition['references'];
        $on = $definition['on'];
        $onDelete = $definition['onDelete'] ?? 'CASCADE';

        $sql = sprintf(
            'ALTER TABLE "%s" ADD CONSTRAINT "%s" FOREIGN KEY ("%s") REFERENCES "%s"("%s") ON DELETE %s',
            $tableName,
            $name,
            $column,
            $on,
            $references,
            $onDelete
        );

        $this->connection->statement($sql);
    }

    /**
     * Get pending changes for an existing table
     */
    protected function getTableChanges(string $tableName, array $definition): array
    {
        // TODO: Implement column comparison
        // For now, we skip modification of existing tables
        return [];
    }

    /**
     * Apply changes to an existing table
     */
    protected function applyTableChanges(string $tableName, array $definition): array
    {
        // TODO: Implement table modification
        // For now, we skip modification of existing tables
        return [];
    }

    /**
     * Mark a table as processed
     */
    protected function markTableProcessed(string $tableName, string $module): void
    {
        $this->schemaState['tables'][$tableName] = [
            'module' => $module,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Load schema state
     */
    protected function loadState(): void
    {
        if (file_exists($this->statePath)) {
            $this->schemaState = require $this->statePath;
        } else {
            $this->schemaState = ['tables' => []];
        }
    }

    /**
     * Save schema state
     */
    protected function saveState(): void
    {
        $dir = dirname($this->statePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }
        save_php_array($this->statePath, $this->schemaState, 'Schema State');
    }
}
