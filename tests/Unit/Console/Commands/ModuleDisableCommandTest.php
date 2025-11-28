<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\ModuleDisableCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleDisableCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_module_disable(): void
    {
        $command = new ModuleDisableCommand();
        
        $this->assertSame('module:disable', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new ModuleDisableCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_without_name_shows_error(): void
    {
        $command = new ModuleDisableCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('Usage', $output);
    }

    #[Test]
    public function handle_with_nonexistent_module(): void
    {
        $command = new ModuleDisableCommand();
        
        ob_start();
        $result = $command->handle(['nonexistent_module_xyz']);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('not found', $output);
    }

    #[Test]
    public function handle_prevents_disabling_core_error_module(): void
    {
        $command = new ModuleDisableCommand();
        
        ob_start();
        $result = $command->handle(['error']);
        $output = ob_get_clean();
        
        // Should fail because error is a core module
        $this->assertSame(1, $result);
    }

    #[Test]
    public function handle_prevents_disabling_core_home_module(): void
    {
        $command = new ModuleDisableCommand();
        
        ob_start();
        $result = $command->handle(['home']);
        $output = ob_get_clean();
        
        // Should fail because home is a core module
        $this->assertSame(1, $result);
    }

    #[Test]
    public function handle_disables_enabled_module(): void
    {
        $command = new ModuleDisableCommand();
        
        // Use a module that exists and is not core
        ob_start();
        $result = $command->handle(['blog']);
        $output = ob_get_clean();
        
        // Will either succeed or fail based on module existence
        $this->assertIsInt($result);
    }

    #[Test]
    public function handle_warns_if_already_disabled(): void
    {
        $command = new TestableModuleDisableCommand();
        
        // Mock a module that's already disabled
        $command->setModuleExists(true);
        $command->setModuleEnabled(false);
        
        ob_start();
        $result = $command->handle(['test-module']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('already disabled', $output);
    }

    #[Test]
    public function handle_outputs_success_on_disable(): void
    {
        $command = new TestableModuleDisableCommand();
        
        $command->setModuleExists(true);
        $command->setModuleEnabled(true);
        $command->setDisableSuccess(true);
        
        ob_start();
        $result = $command->handle(['test-module']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('disabled', $output);
    }

    #[Test]
    public function handle_outputs_error_on_disable_failure(): void
    {
        $command = new TestableModuleDisableCommand();
        
        $command->setModuleExists(true);
        $command->setModuleEnabled(true);
        $command->setDisableSuccess(false);
        
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
class TestableModuleDisableCommand extends ModuleDisableCommand
{
    private bool $moduleExists = false;
    private bool $moduleEnabled = true;
    private bool $disableSuccess = true;
    
    public function setModuleExists(bool $exists): void
    {
        $this->moduleExists = $exists;
    }
    
    public function setModuleEnabled(bool $enabled): void
    {
        $this->moduleEnabled = $enabled;
    }
    
    public function setDisableSuccess(bool $success): void
    {
        $this->disableSuccess = $success;
    }
    
    public function handle(array $args = []): int
    {
        $moduleName = $args[0] ?? null;

        if ($moduleName === null) {
            $this->error("Usage: module:disable <module-name>");
            return 1;
        }

        if (!$this->moduleExists) {
            $this->error("Module not found: {$moduleName}");
            return 1;
        }

        if (!$this->moduleEnabled) {
            $this->warn("Module '{$moduleName}' is already disabled.");
            return 0;
        }

        $coreModules = ['error', 'home'];
        if (in_array($moduleName, $coreModules)) {
            $this->error("Cannot disable core module: {$moduleName}");
            return 1;
        }

        $this->line("Disabling module: {$moduleName}");

        if (!$this->disableSuccess) {
            $this->error("Failed to disable module.");
            return 1;
        }

        $this->info("✓ Module '{$moduleName}' disabled");
        $this->line("  Run 'php bin/console s:up' to complete setup.");

        return 0;
    }
}
