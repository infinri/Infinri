<?php

declare(strict_types=1);


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
use App\Core\Indexer\IndexerRegistry;

/**
 * Index Reindex Command
 * 
 * Runs module indexers.
 */
class IndexReindexCommand extends Command
{
    protected string $name = 'index:reindex';
    protected string $description = 'Run indexers (all or specific)';
    protected array $aliases = ['reindex'];

    protected IndexerRegistry $registry;

    public function __construct()
    {
        $this->registry = $this->getRegistry();
    }

    public function handle(array $args = []): int
    {
        // Parse arguments
        $indexerName = $args[0] ?? null;
        $listOnly = in_array('--list', $args) || in_array('-l', $args);
        $clearFirst = in_array('--clear', $args) || in_array('-c', $args);

        // List indexers
        if ($listOnly || $indexerName === 'list') {
            return $this->listIndexers();
        }

        // Run specific indexer
        if ($indexerName !== null) {
            return $this->runIndexer($indexerName, $clearFirst);
        }

        // Run all indexers
        return $this->runAllIndexers($clearFirst);
    }

    protected function listIndexers(): int
    {
        $this->line("Registered Indexers");
        $this->line(str_repeat('─', 60));

        $indexers = $this->registry->all();

        if (empty($indexers)) {
            $this->warn("No indexers registered.");
            $this->line();
            $this->line("Modules can register indexers via their service provider:");
            $this->line("  \$indexerRegistry->register(new MyIndexer());");
            return 0;
        }

        $this->line();
        $this->line(sprintf("  %-20s %-10s %s", "NAME", "STATUS", "DESCRIPTION"));
        $this->line(str_repeat('─', 60));

        foreach ($indexers as $indexer) {
            $status = $indexer->isValid() ? "\033[32m✓ valid\033[0m" : "\033[33m○ stale\033[0m";
            $this->line(sprintf(
                "  %-20s %-18s %s",
                $indexer->getName(),
                $status,
                substr($indexer->getDescription(), 0, 25)
            ));
        }

        $this->line();
        return 0;
    }

    protected function runIndexer(string $name, bool $clearFirst): int
    {
        $indexer = $this->registry->get($name);

        if ($indexer === null) {
            $this->error("Indexer not found: {$name}");
            $this->line("Run 'index:reindex --list' to see available indexers.");
            return 1;
        }

        $this->line("Running indexer: {$name}");
        $this->line(str_repeat('─', 40));

        if ($clearFirst) {
            $this->line("  Clearing index...");
            $indexer->clear();
        }

        $start = microtime(true);
        $count = $indexer->reindex();
        $duration = round((microtime(true) - $start) * 1000);

        $this->info("  ✓ Indexed {$count} item(s) in {$duration}ms");
        return 0;
    }

    protected function runAllIndexers(bool $clearFirst): int
    {
        $indexers = $this->registry->all();

        if (empty($indexers)) {
            $this->warn("No indexers registered.");
            return 0;
        }

        $this->line("Running all indexers...");
        $this->line(str_repeat('─', 40));

        $totalCount = 0;
        $start = microtime(true);

        foreach ($indexers as $indexer) {
            $name = $indexer->getName();
            $this->line("\n  {$name}:");

            if ($clearFirst) {
                $indexer->clear();
            }

            $count = $indexer->reindex();
            $totalCount += $count;
            $this->line("    ✓ {$count} item(s)");
        }

        $duration = round((microtime(true) - $start) * 1000);

        $this->line(str_repeat('─', 40));
        $this->info("✅ Total: {$totalCount} item(s) in {$duration}ms");
        return 0;
    }

    protected function getRegistry(): IndexerRegistry
    {
        // Try to get from container
        if (function_exists('app')) {
            try {
                return app()->make(IndexerRegistry::class);
            } catch (\Throwable) {}
        }

        return new IndexerRegistry();
    }
}
