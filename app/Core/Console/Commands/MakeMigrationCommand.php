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
use App\Core\Console\Concerns\GeneratesFiles;

/**
 * Make Migration Command
 *
 * Generates a new database migration file.
 */
class MakeMigrationCommand extends Command
{
    use GeneratesFiles;
    protected string $name = 'make:migration';
    protected string $description = 'Generate a new database migration';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $name = $this->requireArgument($args, 0, 'make:migration <name>', 'make:migration create_users_table');
        if ($name === null) {
            return 1;
        }

        $migrationsPath = $this->getRootDir() . '/database/migrations';
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $migrationsPath . '/' . $filename;

        if ($this->pathExists($filepath, "Migration '{$filename}'")) {
            return 1;
        }

        $className = $this->toClassName($name);
        $content = $this->getMigrationTemplate($className, $name);
        $this->writeFile($filepath, $content);

        $this->info("✓ Created migration: {$filename}");
        $this->line("  Path: database/migrations/{$filename}");

        return 0;
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
