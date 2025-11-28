<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Database\MigrationState;

/**
 * Migrate Reset State Command
 * 
 * Clears the "unsafe" migration state after manual recovery.
 */
class MigrateResetStateCommand extends Command
{
    protected string $name = 'migrate:reset-state';
    protected string $description = 'Clear unsafe migration state after manual recovery';
    protected array $aliases = [];

    public function handle(array $args = []): int
    {
        $state = new MigrationState();

        if ($state->isSafe()) {
            $this->info("Migration state is already safe.");
            return 0;
        }

        $failed = $state->getFailedInfo();
        
        $this->line("Current State:");
        $this->line("  • Migration: {$failed['migration']}");
        $this->line("  • Error: {$failed['error']}");
        $this->line("  • Failed at: {$failed['failed_at']}");
        $this->line();

        // Require confirmation unless --force
        if (!in_array('--force', $args) && !in_array('-f', $args)) {
            $this->warn("⚠️  Only reset state after manually fixing the issue!");
            $this->line();
            $this->line("Have you:");
            $this->line("  1. Fixed the migration or database issue?");
            $this->line("  2. Restored from backup if needed?");
            $this->line();
            $this->line("Run with --force to confirm reset.");
            return 1;
        }

        $state->clearFailed();
        $this->info("✓ Migration state cleared. System is now safe.");
        $this->line("  Run 'php bin/console s:up' to continue.");

        return 0;
    }
}
