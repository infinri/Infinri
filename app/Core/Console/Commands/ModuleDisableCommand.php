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
use App\Core\Module\ModuleHookRunner;
use App\Core\Module\ModuleRegistry;

/**
 * Module Disable Command
 */
class ModuleDisableCommand extends Command
{
    protected string $name = 'module:disable';
    protected string $description = 'Disable a module';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $moduleName = $args[0] ?? null;

        if ($moduleName === null) {
            $this->error("Usage: module:disable <module-name>");

            return 1;
        }

        $registry = new ModuleRegistry();

        if (! $registry->has($moduleName)) {
            $this->error("Module not found: {$moduleName}");

            return 1;
        }

        $module = $registry->get($moduleName);

        if (! $module->enabled) {
            $this->warn("Module '{$moduleName}' is already disabled.");

            return 0;
        }

        // Prevent disabling core modules
        $coreModules = ['error', 'home'];
        if (in_array($moduleName, $coreModules, true)) {
            $this->error("Cannot disable core module: {$moduleName}");

            return 1;
        }

        $this->line("Disabling module: {$moduleName}");

        // Run onDisable hook first
        $hookRunner = new ModuleHookRunner($registry);
        $hookRunner->runDisableHook($moduleName);

        // Disable the module
        if (! $registry->disable($moduleName)) {
            $this->error("Failed to disable module.");

            return 1;
        }

        $this->info("✓ Module '{$moduleName}' disabled");
        $this->line("  Run 'php bin/console s:up' to complete setup.");

        return 0;
    }
}
