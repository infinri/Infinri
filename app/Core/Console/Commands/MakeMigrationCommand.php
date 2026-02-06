<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Console\Commands;

use App\Core\Console\Command;

/**
 * Make Migration Command
 *
 * Generates a new database migration file.
 */
class MakeMigrationCommand extends Command
{
    protected string $name = 'make:migration';
    protected string $description = 'Generate a new database migration';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            $this->error("Usage: make:migration <name>");
            $this->line("  Example: make:migration create_users_table");

            return 1;
        }

        $rootDir = $this->getRootDir();
        $migrationsPath = $rootDir . '/database/migrations';

        if (! is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0o755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $migrationsPath . '/' . $filename;

        if (file_exists($filepath)) {
            $this->error("Migration '{$filename}' already exists.");

            return 1;
        }

        $className = $this->getClassName($name);
        $content = $this->getMigrationTemplate($className, $name);
        file_put_contents($filepath, $content);

        $this->info("✓ Created migration: {$filename}");
        $this->line("  Path: database/migrations/{$filename}");

        return 0;
    }

    protected function getClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    protected function getMigrationTemplate(string $className, string $name): string
    {
        $tableName = $this->guessTableName($name);
        $isCreate = str_contains($name, 'create_');

        if ($isCreate) {
            return $this->getCreateTableTemplate($className, $tableName);
        }

        return $this->getModifyTableTemplate($className, $tableName);
    }

    protected function guessTableName(string $name): string
    {
        $name = preg_replace('/^(create_|add_|modify_|update_|drop_)/', '', $name);
        $name = preg_replace('/(_table|_column|_index|_to_\w+|_from_\w+)$/', '', $name);

        return $name;
    }

    protected function getCreateTableTemplate(string $className, string $tableName): string
    {
        return <<<PHP
            <?php declare(strict_types=1);

            use App\\Core\\Database\\Migration;
            use App\\Core\\Database\\Schema\\Blueprint;

            return new class extends Migration
            {
                public function up(): void
                {
                    \$this->schema->create('{$tableName}', function (Blueprint \$table) {
                        \$table->id();
                        // Add columns here
                        \$table->timestamps();
                    });
                }

                public function down(): void
                {
                    \$this->schema->dropIfExists('{$tableName}');
                }
            };
            PHP;
    }

    protected function getModifyTableTemplate(string $className, string $tableName): string
    {
        return <<<PHP
            <?php declare(strict_types=1);

            use App\\Core\\Database\\Migration;
            use App\\Core\\Database\\Schema\\Blueprint;

            return new class extends Migration
            {
                public function up(): void
                {
                    \$this->schema->table('{$tableName}', function (Blueprint \$table) {
                        // Add/modify columns here
                    });
                }

                public function down(): void
                {
                    \$this->schema->table('{$tableName}', function (Blueprint \$table) {
                        // Reverse changes here
                    });
                }
            };
            PHP;
    }
}
