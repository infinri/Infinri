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
namespace Tests\Unit\Database;

use App\Core\Database\MigrationState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigrationStateTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/migration_state_' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }
    }

    #[Test]
    public function is_safe_by_default(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $this->assertTrue($state->isSafe());
    }

    #[Test]
    public function is_not_running_by_default(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $this->assertFalse($state->isRunning());
    }

    #[Test]
    public function mark_started_sets_running_state(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $state->markStarted('CreateUsersTable');
        
        $this->assertTrue($state->isRunning());
    }

    #[Test]
    public function mark_completed_clears_running_state(): void
    {
        $state = new MigrationState($this->tempPath);
        $state->markStarted('CreateUsersTable');
        
        $state->markCompleted('CreateUsersTable');
        
        $this->assertFalse($state->isRunning());
    }

    #[Test]
    public function mark_failed_sets_failed_state(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $state->markFailed('CreateUsersTable', 'SQL Error');
        
        $this->assertFalse($state->isSafe());
    }

    #[Test]
    public function get_failed_info_returns_null_when_safe(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $this->assertNull($state->getFailedInfo());
    }

    #[Test]
    public function get_failed_info_returns_details_when_failed(): void
    {
        $state = new MigrationState($this->tempPath);
        $state->markFailed('CreateUsersTable', 'SQL Error');
        
        $info = $state->getFailedInfo();
        
        $this->assertSame('CreateUsersTable', $info['migration']);
        $this->assertSame('SQL Error', $info['error']);
    }

    #[Test]
    public function clear_failed_resets_state(): void
    {
        $state = new MigrationState($this->tempPath);
        $state->markFailed('CreateUsersTable', 'SQL Error');
        
        $state->clearFailed();
        
        $this->assertTrue($state->isSafe());
    }

    #[Test]
    public function get_state_returns_full_state(): void
    {
        $state = new MigrationState($this->tempPath);
        
        $fullState = $state->getState();
        
        $this->assertIsArray($fullState);
        $this->assertArrayHasKey('status', $fullState);
    }

    #[Test]
    public function state_persists_to_file(): void
    {
        $state1 = new MigrationState($this->tempPath);
        $state1->markStarted('CreateUsersTable');
        
        $state2 = new MigrationState($this->tempPath);
        
        $this->assertTrue($state2->isRunning());
    }

    #[Test]
    public function mark_completed_ignores_wrong_migration(): void
    {
        $state = new MigrationState($this->tempPath);
        $state->markStarted('CreateUsersTable');
        
        $state->markCompleted('DifferentMigration');
        
        $this->assertTrue($state->isRunning());
    }
}
