<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Core\Database\Migrator;
use App\Core\Database\DatabaseException;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testable Migrator that bypasses logging dependencies
 */
class TestableMigrator extends Migrator
{
    // Skip ensureMigrationsTableExists to avoid app() dependency
    protected function ensureMigrationsTableExists(): void
    {
        // Do nothing in tests - assume table exists
    }
    
    // Skip logging to avoid app() dependency
    protected function logMigrationRun(string $direction, string $migration): void
    {
        // Do nothing in tests
    }
    
    // Expose protected methods for testing
    public function testMigrationToClassName(string $migration): string
    {
        return $this->migrationToClassName($migration);
    }
    
    public function testGetAllMigrations(): array
    {
        return $this->getAllMigrations();
    }
    
    public function testResolve(string $migration): mixed
    {
        return $this->resolve($migration);
    }
}

class MigratorTest extends TestCase
{
    private string $tempDir;
    private $connection;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/migrator_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        $this->connection = $this->createMock(ConnectionInterface::class);
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
    public function constructor_sets_properties(): void
    {
        $migrator = new Migrator($this->connection, $this->tempDir);
        
        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    #[Test]
    public function constructor_trims_trailing_slash(): void
    {
        $migrator = new Migrator($this->connection, $this->tempDir . '/');
        
        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    #[Test]
    public function migrate_returns_empty_when_no_pending(): void
    {
        $this->connection->method('select')->willReturn([]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $ran = $migrator->migrate();
        
        $this->assertIsArray($ran);
        $this->assertEmpty($ran);
    }

    #[Test]
    public function status_returns_array(): void
    {
        $this->connection->method('select')->willReturn([]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $status = $migrator->status();
        
        $this->assertIsArray($status);
    }

    #[Test]
    public function status_shows_ran_status_for_migrations(): void
    {
        // Create a migration file
        $this->createMigrationFile('2025_01_01_000001_create_test_table');
        
        // Mock: one migration ran
        $this->connection->method('select')->willReturn([
            ['migration' => '2025_01_01_000001_create_test_table']
        ]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $status = $migrator->status();
        
        $this->assertCount(1, $status);
        $this->assertTrue($status[0]['ran']);
    }

    #[Test]
    public function status_shows_not_ran_for_pending_migrations(): void
    {
        $this->createMigrationFile('2025_01_01_000001_create_test_table');
        
        $this->connection->method('select')->willReturn([]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $status = $migrator->status();
        
        $this->assertCount(1, $status);
        $this->assertFalse($status[0]['ran']);
    }

    #[Test]
    public function rollback_returns_empty_when_nothing_to_rollback(): void
    {
        $this->connection->method('select')->willReturn([]);
        $this->connection->method('selectOne')->willReturn(['batch' => 0]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $rolledBack = $migrator->rollback();
        
        $this->assertIsArray($rolledBack);
        $this->assertEmpty($rolledBack);
    }

    #[Test]
    public function reset_returns_empty_when_no_migrations(): void
    {
        $this->connection->method('select')->willReturn([]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $rolledBack = $migrator->reset();
        
        $this->assertIsArray($rolledBack);
        $this->assertEmpty($rolledBack);
    }

    #[Test]
    public function refresh_calls_reset_and_migrate(): void
    {
        $this->connection->method('select')->willReturn([]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $result = $migrator->refresh();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function get_all_migrations_returns_empty_for_missing_dir(): void
    {
        $migrator = new TestableMigrator($this->connection, '/nonexistent/path');
        
        $result = $migrator->testGetAllMigrations();
        
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_all_migrations_returns_sorted_files(): void
    {
        // Create migration files out of order
        $this->createMigrationFile('2025_01_01_000002_second');
        $this->createMigrationFile('2025_01_01_000001_first');
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $migrations = $migrator->testGetAllMigrations();
        
        $this->assertCount(2, $migrations);
        $this->assertStringContainsString('first', $migrations[0]);
        $this->assertStringContainsString('second', $migrations[1]);
    }

    #[Test]
    public function migration_to_class_name_converts_correctly(): void
    {
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $result = $migrator->testMigrationToClassName('2025_01_01_000001_create_users_table');
        
        $this->assertSame('CreateUsersTable', $result);
    }

    #[Test]
    public function migration_to_class_name_handles_multiple_words(): void
    {
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $result = $migrator->testMigrationToClassName('2025_01_01_000001_add_email_to_users');
        
        $this->assertSame('AddEmailToUsers', $result);
    }

    #[Test]
    public function migration_to_class_name_handles_single_word(): void
    {
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $result = $migrator->testMigrationToClassName('2025_01_01_000001_initial');
        
        $this->assertSame('Initial', $result);
    }

    #[Test]
    public function resolve_throws_for_missing_file(): void
    {
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Migration file not found');
        
        $migrator->testResolve('nonexistent_migration');
    }

    #[Test]
    public function rollback_accepts_steps_parameter(): void
    {
        $this->connection->method('select')->willReturn([]);
        $this->connection->method('selectOne')->willReturn(['batch' => 1]);
        
        $migrator = new TestableMigrator($this->connection, $this->tempDir);
        
        $rolledBack = $migrator->rollback(3);
        
        $this->assertIsArray($rolledBack);
    }

    private function createMigrationFile(string $name): void
    {
        $className = $this->getClassName($name);
        $content = <<<PHP
<?php
use App\Core\Database\Migration;

class {$className} extends Migration
{
    public function up(): void {}
    public function down(): void {}
}
PHP;
        file_put_contents($this->tempDir . '/' . $name . '.php', $content);
    }

    private function getClassName(string $migration): string
    {
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);
        $words = explode('_', $name);
        return implode('', array_map('ucfirst', $words));
    }
}
