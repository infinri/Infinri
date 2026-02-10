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

use App\Core\Console\Application;
use App\Core\Console\Command;

/**
 * Help Command
 *
 * Dynamically displays all registered commands.
 */
class HelpCommand extends Command
{
    protected string $name = 'help';
    protected string $description = 'Show available commands';
    protected array $aliases = [];

    protected ?Application $app = null;

    public function setApplication(Application $app): void
    {
        $this->app = $app;
    }

    public function handle(array $args = []): int
    {
        $this->line("Console Application");
        $this->line(str_repeat('=', 50));
        $this->line();

        $this->line("USAGE:");
        $this->line("  php bin/console <command> [options]");
        $this->line();

        // Get commands from application if available
        if ($this->app !== null) {
            $this->displayDynamicCommands();
        } else {
            $this->displayStaticCommands();
        }

        return 0;
    }

    protected function displayDynamicCommands(): void
    {
        $metadata = $this->app->getCommandsMetadata();

        // Group by prefix
        $groups = [];
        foreach ($metadata as $name => $info) {
            $prefix = str_contains($name, ':') ? explode(':', $name)[0] : 'general';
            $groups[$prefix][$name] = $info;
        }

        // Sort groups
        ksort($groups);

        foreach ($groups as $group => $commands) {
            $this->line(strtoupper($group) . " COMMANDS:");

            ksort($commands);
            foreach ($commands as $name => $info) {
                $aliases = ! empty($info['aliases']) ? ' (' . implode(', ', $info['aliases']) . ')' : '';
                $desc = $info['description'] ?: 'No description';
                $this->line(sprintf("  %-24s %s", $name . $aliases, $desc));
            }
            $this->line();
        }
    }

    protected function displayStaticCommands(): void
    {
        $this->line("COMMANDS:");
        $this->line("  help                     Show this help message");
        $this->line("  setup:install (s:i)      Interactive .env setup");
        $this->line("  setup:update (s:up)      Run migrations, clear cache");
        $this->line("  setup:permissions (s:p)  Fix file permissions");
        $this->line();
    }
}
