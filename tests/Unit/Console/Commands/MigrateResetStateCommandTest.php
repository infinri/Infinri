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
namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\MigrateResetStateCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigrateResetStateCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_migrate_reset_state(): void
    {
        $command = new MigrateResetStateCommand();
        
        $this->assertSame('migrate:reset-state', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new MigrateResetStateCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_returns_zero_when_safe(): void
    {
        $command = new MigrateResetStateCommand();
        
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('safe', strtolower($output));
    }

    #[Test]
    public function handle_without_force_requires_confirmation(): void
    {
        $command = new MigrateResetStateCommand();
        
        // Create unsafe state first
        $state = new \App\Core\Database\MigrationState();
        $state->markFailed('test_migration', 'Test error');
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        // Without force, should return 1
        $this->assertSame(1, $result);
        $this->assertStringContainsString('--force', $output);
        
        // Clean up
        $state->clearFailed();
    }

    #[Test]
    public function handle_with_force_clears_state(): void
    {
        $command = new MigrateResetStateCommand();
        
        // Create unsafe state first
        $state = new \App\Core\Database\MigrationState();
        $state->markFailed('test_migration', 'Test error');
        
        ob_start();
        $result = $command->handle(['--force']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('cleared', strtolower($output));
    }

    #[Test]
    public function handle_with_short_force_flag(): void
    {
        $command = new MigrateResetStateCommand();
        
        // Create unsafe state first
        $state = new \App\Core\Database\MigrationState();
        $state->markFailed('test_migration', 'Test error');
        
        ob_start();
        $result = $command->handle(['-f']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
    }
}
