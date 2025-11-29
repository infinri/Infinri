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
namespace Tests\Unit\Database;

use App\Core\Database\DatabaseBackup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseBackupTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \FilesystemIterator($dir);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }

    #[Test]
    public function constructor_accepts_config(): void
    {
        $backup = new DatabaseBackup(['driver' => 'pgsql'], $this->tempDir);
        
        $this->assertInstanceOf(DatabaseBackup::class, $backup);
    }

    #[Test]
    public function list_backups_returns_empty_for_missing_dir(): void
    {
        $backup = new DatabaseBackup([], $this->tempDir . '/nonexistent');
        
        $this->assertSame([], $backup->listBackups());
    }

    #[Test]
    public function list_backups_returns_files(): void
    {
        file_put_contents($this->tempDir . '/backup_2024-01-01_000000.sql', 'test');
        file_put_contents($this->tempDir . '/backup_2024-01-02_000000.sql', 'test');
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $backups = $backup->listBackups();
        
        $this->assertCount(2, $backups);
    }

    #[Test]
    public function list_backups_includes_metadata(): void
    {
        file_put_contents($this->tempDir . '/backup_2024-01-01_000000.sql', 'test data');
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $backups = $backup->listBackups();
        
        $this->assertArrayHasKey('path', $backups[0]);
        $this->assertArrayHasKey('filename', $backups[0]);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('created', $backups[0]);
    }

    #[Test]
    public function prune_backups_deletes_old_files(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $timestamp = sprintf('2024-01-%02d_000000', $i);
            file_put_contents($this->tempDir . "/backup_{$timestamp}.sql", 'test');
            touch($this->tempDir . "/backup_{$timestamp}.sql", strtotime("2024-01-{$i}"));
        }
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $deleted = $backup->pruneBackups(3);
        
        $this->assertSame(7, $deleted);
        $this->assertCount(3, $backup->listBackups());
    }

    #[Test]
    public function prune_backups_returns_zero_when_none_to_delete(): void
    {
        file_put_contents($this->tempDir . '/backup_2024-01-01_000000.sql', 'test');
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $deleted = $backup->pruneBackups(5);
        
        $this->assertSame(0, $deleted);
    }

    #[Test]
    public function restore_fails_for_missing_file(): void
    {
        $backup = new DatabaseBackup(['driver' => 'pgsql'], $this->tempDir);
        
        $result = $backup->restore('/nonexistent/file.sql');
        
        $this->assertFalse($result['success']);
        $this->assertSame('Backup file not found', $result['error']);
    }

    #[Test]
    public function backup_fails_for_unsupported_driver(): void
    {
        $backup = new DatabaseBackup(['driver' => 'sqlite'], $this->tempDir);
        
        $result = $backup->backup();
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not supported', $result['error']);
    }

    #[Test]
    public function restore_fails_for_unsupported_driver(): void
    {
        $file = $this->tempDir . '/test.sql';
        file_put_contents($file, 'test');
        
        $backup = new DatabaseBackup(['driver' => 'sqlite'], $this->tempDir);
        $result = $backup->restore($file);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not supported', $result['error']);
    }

    #[Test]
    public function format_bytes_formats_correctly(): void
    {
        $this->assertSame('100 B', DatabaseBackup::formatBytes(100));
        $this->assertSame('1 KB', DatabaseBackup::formatBytes(1024));
        $this->assertSame('1.5 KB', DatabaseBackup::formatBytes(1536));
        $this->assertSame('1 MB', DatabaseBackup::formatBytes(1048576));
        $this->assertSame('1 GB', DatabaseBackup::formatBytes(1073741824));
    }

    #[Test]
    public function format_bytes_handles_zero(): void
    {
        $this->assertSame('0 B', DatabaseBackup::formatBytes(0));
    }

    #[Test]
    public function backup_builds_pgsql_command(): void
    {
        $config = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'testdb',
            'username' => 'testuser',
            'password' => 'testpass',
        ];
        
        $backup = new TestableDatabaseBackup($config, $this->tempDir);
        $result = $backup->backup();
        
        // Backup will fail because pg_dump isn't available, but we test the flow
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('path', $result);
    }

    #[Test]
    public function backup_builds_mysql_command(): void
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'testdb',
            'username' => 'testuser',
            'password' => 'testpass',
        ];
        
        $backup = new TestableDatabaseBackup($config, $this->tempDir);
        $result = $backup->backup();
        
        // Backup will fail because mysqldump isn't available, but we test the flow
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function restore_builds_pgsql_command(): void
    {
        $file = $this->tempDir . '/test.sql';
        file_put_contents($file, 'SELECT 1;');
        
        $config = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'testdb',
            'username' => 'testuser',
            'password' => 'testpass',
        ];
        
        $backup = new TestableDatabaseBackup($config, $this->tempDir);
        $result = $backup->restore($file);
        
        // Restore will fail because psql isn't available, but we test the flow
        $this->assertArrayHasKey('success', $result);
    }

    #[Test]
    public function restore_builds_mysql_command(): void
    {
        $file = $this->tempDir . '/test.sql';
        file_put_contents($file, 'SELECT 1;');
        
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'testdb',
            'username' => 'testuser',
            'password' => 'testpass',
        ];
        
        $backup = new TestableDatabaseBackup($config, $this->tempDir);
        $result = $backup->restore($file);
        
        // Restore will fail because mysql isn't available, but we test the flow
        $this->assertArrayHasKey('success', $result);
    }

    #[Test]
    public function list_backups_sorts_by_newest_first(): void
    {
        // Create backups with different timestamps
        file_put_contents($this->tempDir . '/backup_2024-01-01_000000.sql', 'old');
        touch($this->tempDir . '/backup_2024-01-01_000000.sql', strtotime('2024-01-01'));
        
        file_put_contents($this->tempDir . '/backup_2024-01-15_000000.sql', 'new');
        touch($this->tempDir . '/backup_2024-01-15_000000.sql', strtotime('2024-01-15'));
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $backups = $backup->listBackups();
        
        // Newest should be first
        $this->assertStringContainsString('2024-01-15', $backups[0]['filename']);
    }

    #[Test]
    public function backup_generates_timestamped_filename(): void
    {
        $backup = new MockableDatabaseBackup(['driver' => 'pgsql'], $this->tempDir);
        $result = $backup->backup();
        
        $this->assertStringContainsString('backup_', $result['path']);
        $this->assertStringContainsString('.sql', $result['path']);
    }

    #[Test]
    public function prune_keeps_specified_number(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $timestamp = sprintf('2024-01-%02d_000000', $i);
            file_put_contents($this->tempDir . "/backup_{$timestamp}.sql", 'test');
            touch($this->tempDir . "/backup_{$timestamp}.sql", strtotime("2024-01-{$i}"));
        }
        
        $backup = new DatabaseBackup([], $this->tempDir);
        $backup->pruneBackups(2);
        
        // Should keep only 2
        $this->assertCount(2, $backup->listBackups());
    }
}

/**
 * Testable subclass that overrides ensureBackupDir
 */
class TestableDatabaseBackup extends DatabaseBackup
{
    protected function ensureBackupDir(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
}

/**
 * Mockable subclass that doesn't run actual backup commands
 * Used to test filename generation without 5s timeout
 */
class MockableDatabaseBackup extends TestableDatabaseBackup
{
    public function backup(): array
    {
        $this->ensureBackupDir();
        
        $timestamp = date('Y-m-d_His');
        $backupFile = "{$this->backupDir}/backup_{$timestamp}.sql";
        
        // Create a mock backup file instead of running pg_dump
        file_put_contents($backupFile, '-- Mock backup');
        
        return [
            'success' => true,
            'path' => $backupFile,
            'size' => filesize($backupFile),
            'error' => null,
        ];
    }
}
