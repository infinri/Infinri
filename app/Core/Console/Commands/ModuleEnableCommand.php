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
 * Module Enable Command
 */
class ModuleEnableCommand extends Command
{
    protected string $name = 'module:enable';
    protected string $description = 'Enable a module';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $moduleName = $args[0] ?? null;

        if ($moduleName === null) {
            $this->error("Usage: module:enable <module-name>");

            return 1;
        }

        $registry = new ModuleRegistry();

        if (! $registry->has($moduleName)) {
            $this->error("Module not found: {$moduleName}");

            return 1;
        }

        $module = $registry->get($moduleName);

        if ($module->enabled) {
            $this->warn("Module '{$moduleName}' is already enabled.");

            return 0;
        }

        $this->line("Enabling module: {$moduleName}");

        // Enable the module
        if (! $registry->enable($moduleName)) {
            $this->error("Failed to enable module.");

            return 1;
        }

        // Run onEnable hook
        $hookRunner = new ModuleHookRunner($registry);
        $hookRunner->runEnableHook($moduleName);

        $this->info("✓ Module '{$moduleName}' enabled");
        $this->line("  Run 'php bin/console s:up' to complete setup.");

        return 0;
    }
}
