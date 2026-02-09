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
 * Module Make Command
 *
 * Generates a new module scaffold with standard directory structure.
 */
class ModuleMakeCommand extends Command
{
    use GeneratesFiles;
    protected string $name = 'module:make';
    protected string $description = 'Generate a new module scaffold';
    protected array $aliases = ['make:module'];

    public function handle(array $args = []): int
    {
        $name = $this->requireArgument($args, 0, 'module:make <name>', 'module:make blog');
        if ($name === null) {
            return 1;
        }

        $name = strtolower($name);
        $className = ucfirst($name);

        $rootDir = $this->getRootDir();
        $modulePath = $rootDir . '/app/modules/' . $name;

        if ($this->pathExists($modulePath, "Module '{$name}'")) {
            return 1;
        }

        $this->line("Creating module: {$name}");
        $this->line(str_repeat('─', 40));

        // Create directories
        $dirs = [
            $name,
            $name . '/Providers',
            $name . '/Controllers',
            $name . '/Models',
            $name . '/view/frontend/templates',
        ];

        foreach ($dirs as $dir) {
            $path = $rootDir . '/app/modules/' . $dir;
            ensure_directory($path);
            $this->line("  ✓ Created: {$dir}");
        }

        // Create files
        $this->createModuleFile($modulePath, $name, $className);
        $this->createIndexFile($modulePath, $name);
        $this->createConfigFile($modulePath);
        $this->createEventsFile($modulePath);
        $this->createHooksFile($modulePath, $className);
        $this->createServiceProvider($modulePath, $className);

        $this->line(str_repeat('─', 40));
        $this->info("✅ Module '{$name}' created!");
        $this->line("\nNext steps:");
        $this->line("  1. Edit app/modules/{$name}/module.php");
        $this->line("  2. Run: php bin/console s:up");
        $this->line("  3. Add routes or functionality");

        return 0;
    }

    protected function createModuleFile(string $path, string $name, string $className): void
    {
        $content = <<<PHP
            <?php
            return [
                'name' => '{$className}',
                'version' => '1.0.0',
                'enabled' => true,
                'dependencies' => [],
                'description' => '{$className} module',
            ];
            PHP;
        $this->writeFile($path . '/module.php', $content, 'module.php');
    }

    protected function createIndexFile(string $path, string $name): void
    {
        $content = <<<HTML
            <h1>Welcome to {$name}</h1>
            <p>Edit this file at: app/modules/{$name}/index.php</p>
            HTML;
        $this->writeFile($path . '/index.php', $content, 'index.php');
    }

    protected function createConfigFile(string $path): void
    {
        $content = <<<'PHP'
            <?php
            return [
                // Module configuration
            ];
            PHP;
        $this->writeFile($path . '/config.php', $content, 'config.php');
    }

    protected function createEventsFile(string $path): void
    {
        $content = <<<'PHP'
            <?php
            return [
                // Event listeners: 'event.name' => [Listener::class, 'method']
            ];
            PHP;
        $this->writeFile($path . '/events.php', $content, 'events.php');
    }

    protected function createHooksFile(string $path, string $className): void
    {
        $content = <<<PHP
            <?php
            return [
                'onInstall' => function() {
                    // Run when module is first installed
                },
                'onUpgrade' => function(string \$fromVersion, string \$toVersion) {
                    // Run when module version changes
                },
                'beforeSetup' => function() {
                    // Run before s:up
                },
                'afterSetup' => function() {
                    // Run after s:up
                },
            ];
            PHP;
        $this->writeFile($path . '/hooks.php', $content, 'hooks.php');
    }

    protected function createServiceProvider(string $path, string $className): void
    {
        $content = <<<PHP
            <?php declare(strict_types=1);

            namespace App\\Modules\\{$className}\\Providers;

            use App\\Core\\Providers\\ServiceProvider;

            class {$className}ServiceProvider extends ServiceProvider
            {
                public function register(): void
                {
                    // Register bindings
                }

                public function boot(): void
                {
                    // Bootstrap services
                }
            }
            PHP;
        $this->writeFile($path . '/Providers/' . $className . 'ServiceProvider.php', $content, "Providers/{$className}ServiceProvider.php");
    }
}
