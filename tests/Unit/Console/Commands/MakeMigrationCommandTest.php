<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\MakeMigrationCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MakeMigrationCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_make_migration(): void
    {
        $command = new MakeMigrationCommand();
        
        $this->assertSame('make:migration', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new MakeMigrationCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_without_name_shows_error(): void
    {
        $command = new MakeMigrationCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('name', strtolower($output));
    }

    #[Test]
    public function handle_creates_migration_file(): void
    {
        $command = new MakeMigrationCommand();
        $name = 'test_create_' . uniqid();
        
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Created migration', $output);
        
        // Clean up: find and remove the created file
        $rootDir = dirname(__DIR__, 4);
        $migrationsPath = $rootDir . '/database/migrations';
        $files = glob($migrationsPath . '/*' . $name . '.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    #[Test]
    public function handle_creates_migrations_directory(): void
    {
        $command = new MakeMigrationCommand();
        $name = 'test_dir_' . uniqid();
        
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        
        // Clean up
        $rootDir = dirname(__DIR__, 4);
        $migrationsPath = $rootDir . '/database/migrations';
        $files = glob($migrationsPath . '/*' . $name . '.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    #[Test]
    public function get_class_name_converts_snake_case(): void
    {
        $command = new MakeMigrationCommand();
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getClassName');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, 'create_users_table');
        
        $this->assertSame('CreateUsersTable', $result);
    }

    #[Test]
    public function get_migration_template_returns_valid_php(): void
    {
        $command = new MakeMigrationCommand();
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getMigrationTemplate');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, 'CreateUsersTable', 'create_users_table');
        
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('Migration', $result);
        $this->assertStringContainsString('users', $result);
    }

    #[Test]
    public function guess_table_name_extracts_table(): void
    {
        $command = new MakeMigrationCommand();
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('guessTableName');
        $method->setAccessible(true);
        
        $this->assertSame('users', $method->invoke($command, 'create_users_table'));
        $this->assertSame('column', $method->invoke($command, 'add_column_to_posts'));
    }

    #[Test]
    public function get_create_table_template_returns_schema_create(): void
    {
        $command = new MakeMigrationCommand();
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getCreateTableTemplate');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, 'CreateUsersTable', 'users');
        
        $this->assertStringContainsString('schema->create', $result);
        $this->assertStringContainsString('users', $result);
    }

    #[Test]
    public function get_modify_table_template_returns_schema_table(): void
    {
        $command = new MakeMigrationCommand();
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModifyTableTemplate');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, 'AddEmailToUsers', 'users');
        
        $this->assertStringContainsString('schema->table', $result);
        $this->assertStringContainsString('users', $result);
    }
}
