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
namespace Tests\Unit\Console;

use App\Core\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_name(): void
    {
        $command = new TestCommand();
        
        $this->assertSame('test:command', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new TestCommand();
        
        $this->assertSame('A test command', $command->getDescription());
    }

    #[Test]
    public function get_aliases_returns_aliases(): void
    {
        $command = new TestCommand();
        
        $this->assertSame(['t:c'], $command->getAliases());
    }

    #[Test]
    public function handle_is_called_by_execute(): void
    {
        $command = new TestCommand();
        
        $result = $command->execute('test:command', ['--verbose']);
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_returns_exit_code(): void
    {
        $command = new TestCommand();
        
        $result = $command->handle();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function line_outputs_text(): void
    {
        $command = new OutputTestCommand();
        
        ob_start();
        $command->testLine('Hello');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Hello', $output);
    }

    #[Test]
    public function info_outputs_green_text(): void
    {
        $command = new OutputTestCommand();
        
        ob_start();
        $command->testInfo('Success');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Success', $output);
        $this->assertStringContainsString("\033[32m", $output);
    }

    #[Test]
    public function error_outputs_red_text(): void
    {
        $command = new OutputTestCommand();
        
        ob_start();
        $command->testError('Failure');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Failure', $output);
        $this->assertStringContainsString("\033[31m", $output);
    }

    #[Test]
    public function warn_outputs_yellow_text(): void
    {
        $command = new OutputTestCommand();
        
        ob_start();
        $command->testWarn('Warning');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Warning', $output);
        $this->assertStringContainsString("\033[33m", $output);
    }

    #[Test]
    public function header_outputs_bordered_text(): void
    {
        $command = new OutputTestCommand();
        
        ob_start();
        $command->testHeader('Title');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Title', $output);
        $this->assertStringContainsString('╔', $output);
        $this->assertStringContainsString('╚', $output);
    }

    #[Test]
    public function get_root_dir_returns_path(): void
    {
        $command = new OutputTestCommand();
        
        $path = $command->testGetRootDir();
        
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }
}

class TestCommand extends Command
{
    protected string $name = 'test:command';
    protected string $description = 'A test command';
    protected array $aliases = ['t:c'];

    public function handle(array $args = []): int
    {
        return 0;
    }
}

class OutputTestCommand extends Command
{
    protected string $name = 'output:test';
    protected string $description = 'Output test command';

    public function handle(array $args = []): int
    {
        return 0;
    }

    public function testLine(string $message): void
    {
        $this->line($message);
    }

    public function testInfo(string $message): void
    {
        $this->info($message);
    }

    public function testError(string $message): void
    {
        $this->error($message);
    }

    public function testWarn(string $message): void
    {
        $this->warn($message);
    }

    public function testHeader(string $text): void
    {
        $this->header($text);
    }

    public function testGetRootDir(): string
    {
        return $this->getRootDir();
    }
}
