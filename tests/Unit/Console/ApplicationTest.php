<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Core\Console\Application;
use App\Core\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    #[Test]
    public function constructor_discovers_core_commands(): void
    {
        $app = new Application();
        $commands = $app->getCommands();
        
        $this->assertArrayHasKey('help', $commands);
    }

    #[Test]
    public function get_commands_returns_registered_commands(): void
    {
        $app = new Application();
        $commands = $app->getCommands();
        
        $this->assertArrayHasKey('cache:clear', $commands);
    }

    #[Test]
    public function get_aliases_returns_aliases(): void
    {
        $app = new Application();
        $aliases = $app->getAliases();
        
        $this->assertArrayHasKey('cc', $aliases);
        $this->assertSame('cache:clear', $aliases['cc']);
    }

    #[Test]
    public function get_commands_metadata_returns_array(): void
    {
        $app = new Application();
        
        $metadata = $app->getCommandsMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertNotEmpty($metadata);
    }

    #[Test]
    public function metadata_includes_description(): void
    {
        $app = new Application();
        $metadata = $app->getCommandsMetadata();
        
        $this->assertArrayHasKey('help', $metadata);
        $this->assertArrayHasKey('description', $metadata['help']);
    }

    #[Test]
    public function register_class_adds_command(): void
    {
        $app = new Application();
        
        $app->registerClass(TestConsoleCommand::class);
        
        $commands = $app->getCommands();
        $this->assertArrayHasKey('test:console', $commands);
    }

    #[Test]
    public function register_adds_command_by_name(): void
    {
        $app = new Application();
        
        $app->register('custom', TestConsoleCommand::class);
        
        $commands = $app->getCommands();
        $this->assertArrayHasKey('custom', $commands);
    }

    #[Test]
    public function alias_registers_alias(): void
    {
        $app = new Application();
        
        $app->alias('h', 'help');
        
        $aliases = $app->getAliases();
        $this->assertSame('help', $aliases['h']);
    }

    #[Test]
    public function run_executes_command(): void
    {
        $app = new Application();
        
        ob_start();
        $result = $app->run(['bin/console', 'help']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function run_shows_help_when_no_args(): void
    {
        $app = new Application();
        
        ob_start();
        $result = $app->run(['bin/console']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('USAGE', $output);
    }

    #[Test]
    public function run_returns_error_for_unknown_command(): void
    {
        $app = new Application();
        
        ob_start();
        $result = $app->run(['bin/console', 'unknown:command']);
        ob_end_clean();
        
        $this->assertSame(1, $result);
    }
}

class TestConsoleCommand extends Command
{
    protected string $name = 'test:console';
    protected string $description = 'Test console command';

    public function handle(array $args = []): int
    {
        return 0;
    }
}
