<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Module\ModuleRegistry;

/**
 * Module List Command
 * 
 * Lists all registered modules.
 */
class ModuleListCommand extends Command
{
    protected string $name = 'module:list';
    protected string $description = 'List all registered modules';
    protected array $aliases = ['m:l'];

    public function handle(array $args = []): int
    {
        $registry = new ModuleRegistry();
        
        // Check for --rebuild flag
        if (in_array('--rebuild', $args) || in_array('-r', $args)) {
            $this->line("Rebuilding module registry...");
            $registry->rebuild();
            $this->info("✓ Registry rebuilt");
            $this->line();
        } else {
            $registry->load();
        }

        $modules = $registry->all();
        $loadOrder = $registry->getLoadOrder();

        if (empty($modules)) {
            $this->warn("No modules found.");
            return 0;
        }

        $this->line("Registered Modules (" . count($modules) . ")");
        $this->line(str_repeat('─', 60));
        $this->line();

        // Header
        $this->line(sprintf(
            "  %-15s %-10s %-8s %-10s %s",
            "NAME", "VERSION", "STATUS", "FEATURES", "DESCRIPTION"
        ));
        $this->line(str_repeat('─', 70));

        foreach ($modules as $module) {
            $status = $module->enabled ? '✓' : '✗';
            $statusColor = $module->enabled ? "\033[32m" : "\033[31m";
            
            // Feature indicators
            $features = [];
            if (!empty($module->providers)) $features[] = 'P';
            if (!empty($module->commands)) $features[] = 'C';
            if ($module->eventsFile) $features[] = 'E';
            if ($module->configFile) $features[] = 'F';
            $featureStr = implode('', $features) ?: '-';
            
            $this->line(sprintf(
                "  %-15s %-10s %s%-8s\033[0m %-10s %s",
                $module->name,
                $module->version,
                $statusColor,
                $status . ($module->enabled ? ' on' : ' off'),
                $featureStr,
                substr($module->description, 0, 25)
            ));
        }

        $this->line();
        $this->line("Load Order: " . implode(' → ', $loadOrder));
        $this->line();
        $this->line("Features: P=Providers, C=Commands, E=Events, F=Config");
        $this->line();

        return 0;
    }
}
