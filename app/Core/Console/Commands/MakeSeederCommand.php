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
 * Make Seeder Command
 *
 * Generates a new database seeder file.
 */
class MakeSeederCommand extends Command
{
    use GeneratesFiles;
    protected string $name = 'make:seeder';
    protected string $description = 'Generate a new database seeder';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $name = $this->requireArgument($args, 0, 'make:seeder <name>', 'make:seeder PostSeeder');
        if ($name === null) {
            return 1;
        }

        if (! str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $className = $this->toClassName($name);
        $seedersPath = $this->getRootDir() . '/database/seeders';
        $filename = "{$className}.php";
        $filepath = $seedersPath . '/' . $filename;

        if ($this->pathExists($filepath, "Seeder '{$className}'")) {
            return 1;
        }

        $modelName = str_replace('Seeder', '', $className);
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . 's';

        $content = <<<PHP
            <?php declare(strict_types=1);

            namespace Database\\Seeders;

            use App\\Core\\Database\\Seeder;

            class {$className} extends Seeder
            {
                public function run(): void
                {
                    // Example:
                    // \$this->db->table('{$tableName}')->insert([
                    //     'name' => 'Example',
                    //     'created_at' => now(),
                    // ]);
                }
            }
            PHP;

        $this->writeFile($filepath, $content);

        $this->info("✓ Created seeder: {$filename}");
        $this->line("  Path: database/seeders/{$filename}");

        return 0;
    }
}
