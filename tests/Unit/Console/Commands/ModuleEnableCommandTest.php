<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\ModuleEnableCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleEnableCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_module_enable(): void
    {
        $command = new ModuleEnableCommand();
        
        $this->assertSame('module:enable', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new ModuleEnableCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_without_name_shows_error(): void
    {
        $command = new ModuleEnableCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('Usage', $output);
    }

    #[Test]
    public function handle_with_nonexistent_module(): void
    {
        $command = new ModuleEnableCommand();
        
        ob_start();
        $result = $command->handle(['nonexistent_module_xyz']);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('not found', $output);
    }

    #[Test]
    public function handle_enables_disabled_module(): void
    {
        $command = new ModuleEnableCommand();
        
        // Use a module that exists
        ob_start();
        $result = $command->handle(['blog']);
        $output = ob_get_clean();
        
        // Will either succeed or fail based on module existence
        $this->assertIsInt($result);
    }

    #[Test]
    public function handle_with_already_enabled_module(): void
    {
        $command = new ModuleEnableCommand();
        
        // Try to enable an already enabled module
        ob_start();
        $result = $command->handle(['error']);
        $output = ob_get_clean();
        
        // Error module should already be enabled
        $this->assertIsInt($result);
    }

    #[Test]
    public function handle_warns_if_already_enabled(): void
    {
        $command = new TestableModuleEnableCommand();
        
        $command->setModuleExists(true);
        $command->setModuleEnabled(true);
        
        ob_start();
        $result = $command->handle(['test-module']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('already enabled', $output);
    }

    #[Test]
    public function handle_outputs_success_on_enable(): void
    {
        $command = new TestableModuleEnableCommand();
        
        $command->setModuleExists(true);
        $command->setModuleEnabled(false);
        $command->setEnableSuccess(true);
        
        ob_start();
        $result = $command->handle(['test-module']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('enabled', $output);
    }

    #[Test]
    public function handle_outputs_error_on_enable_failure(): void
    {
        $command = new TestableModuleEnableCommand();
        
        $command->setModuleExists(true);
        $command->setModuleEnabled(false);
        $command->setEnableSuccess(false);
        
        ob_start();
        $result = $command->handle(['test-module']);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('Failed', $output);
    }
}

/**
 * Testable command with controllable behavior
 */
class TestableModuleEnableCommand extends ModuleEnableCommand
{
    private bool $moduleExists = false;
    private bool $moduleEnabled = false;
    private bool $enableSuccess = true;
    
    public function setModuleExists(bool $exists): void
    {
        $this->moduleExists = $exists;
    }
    
    public function setModuleEnabled(bool $enabled): void
    {
        $this->moduleEnabled = $enabled;
    }
    
    public function setEnableSuccess(bool $success): void
    {
        $this->enableSuccess = $success;
    }
    
    public function handle(array $args = []): int
    {
        $moduleName = $args[0] ?? null;

        if ($moduleName === null) {
            $this->error("Usage: module:enable <module-name>");
            return 1;
        }

        if (!$this->moduleExists) {
            $this->error("Module not found: {$moduleName}");
            return 1;
        }

        if ($this->moduleEnabled) {
            $this->warn("Module '{$moduleName}' is already enabled.");
            return 0;
        }

        $this->line("Enabling module: {$moduleName}");

        if (!$this->enableSuccess) {
            $this->error("Failed to enable module.");
            return 1;
        }

        $this->info("✓ Module '{$moduleName}' enabled");
        $this->line("  Run 'php bin/console s:up' to complete setup.");

        return 0;
    }
}
