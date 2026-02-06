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
namespace Tests\Unit\Module;

use App\Core\Module\ModuleHookRunner;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleHookRunnerTest extends TestCase
{
    private string $tempDir;
    private string $statePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/hook_runner_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->statePath = $this->tempDir . '/state.php';
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
    public function constructor_creates_instance(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertInstanceOf(ModuleHookRunner::class, $runner);
    }

    #[Test]
    public function needs_install_returns_true_for_new_module(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertTrue($runner->needsInstall('newmodule'));
    }

    #[Test]
    public function needs_install_returns_false_for_installed_module(): void
    {
        // Create state file with installed module
        file_put_contents($this->statePath, "<?php return ['installed' => ['test' => '1.0.0']];");
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertFalse($runner->needsInstall('test'));
    }

    #[Test]
    public function get_installed_version_returns_version(): void
    {
        file_put_contents($this->statePath, "<?php return ['installed' => ['test' => '1.0.0']];");
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertSame('1.0.0', $runner->getInstalledVersion('test'));
    }

    #[Test]
    public function get_installed_version_returns_null_for_unknown(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertNull($runner->getInstalledVersion('unknown'));
    }

    #[Test]
    public function mark_installed_saves_version(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $runner->markInstalled('test', '2.0.0');
        
        $this->assertSame('2.0.0', $runner->getInstalledVersion('test'));
    }

    #[Test]
    public function needs_upgrade_returns_false_for_not_installed(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'test', 'version' => '2.0.0'], $this->tempDir);
        
        $this->assertFalse($runner->needsUpgrade($module));
    }

    #[Test]
    public function needs_upgrade_returns_true_for_newer_version(): void
    {
        file_put_contents($this->statePath, "<?php return ['installed' => ['test' => '1.0.0']];");
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'test', 'version' => '2.0.0'], $this->tempDir);
        
        $this->assertTrue($runner->needsUpgrade($module));
    }

    #[Test]
    public function needs_upgrade_returns_false_for_same_version(): void
    {
        file_put_contents($this->statePath, "<?php return ['installed' => ['test' => '1.0.0']];");
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'test', 'version' => '1.0.0'], $this->tempDir);
        
        $this->assertFalse($runner->needsUpgrade($module));
    }

    #[Test]
    public function run_hook_returns_false_for_missing_hooks_file(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'test'], $this->tempDir);
        
        $this->assertFalse($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_enable_hook_returns_false_for_unknown_module(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('get')->willReturn(null);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertFalse($runner->runEnableHook('unknown'));
    }

    #[Test]
    public function run_disable_hook_returns_false_for_unknown_module(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('get')->willReturn(null);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertFalse($runner->runDisableHook('unknown'));
    }

    #[Test]
    public function run_setup_hooks_returns_results_array(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $results = $runner->runSetupHooks();
        
        $this->assertArrayHasKey('installed', $results);
        $this->assertArrayHasKey('upgraded', $results);
        $this->assertArrayHasKey('beforeSetup', $results);
        $this->assertArrayHasKey('afterSetup', $results);
    }

    #[Test]
    public function run_after_setup_hooks_returns_array(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $results = $runner->runAfterSetupHooks();
        
        $this->assertIsArray($results);
    }

    #[Test]
    public function run_hook_returns_false_for_non_array_hooks(): void
    {
        // Create module directory with invalid hooks file
        $moduleDir = $this->tempDir . '/testmod';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return "not an array";');
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'testmod'], $moduleDir);
        
        $this->assertFalse($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_hook_returns_false_for_missing_hook(): void
    {
        $moduleDir = $this->tempDir . '/testmod2';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["otherHook" => fn() => true];');
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'testmod2'], $moduleDir);
        
        $this->assertFalse($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_hook_returns_false_for_non_callable(): void
    {
        $moduleDir = $this->tempDir . '/testmod3';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onInstall" => "not callable"];');
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'testmod3'], $moduleDir);
        
        $this->assertFalse($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_hook_executes_callable(): void
    {
        $moduleDir = $this->tempDir . '/testmod4';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onInstall" => fn() => null];');
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'testmod4'], $moduleDir);
        
        $this->assertTrue($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_hook_returns_false_on_exception(): void
    {
        $moduleDir = $this->tempDir . '/testmod5';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onInstall" => fn() => throw new \Exception("Test error")];');
        
        $registry = $this->createMock(ModuleRegistry::class);
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $module = new ModuleDefinition(['name' => 'testmod5'], $moduleDir);
        
        $this->assertFalse($runner->runHook($module, 'onInstall'));
    }

    #[Test]
    public function run_enable_hook_executes_for_known_module(): void
    {
        $moduleDir = $this->tempDir . '/testmod6';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onEnable" => fn() => null];');
        
        $module = new ModuleDefinition(['name' => 'testmod6'], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('get')->with('testmod6')->willReturn($module);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertTrue($runner->runEnableHook('testmod6'));
    }

    #[Test]
    public function run_disable_hook_executes_for_known_module(): void
    {
        $moduleDir = $this->tempDir . '/testmod7';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onDisable" => fn() => null];');
        
        $module = new ModuleDefinition(['name' => 'testmod7'], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('get')->with('testmod7')->willReturn($module);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $this->assertTrue($runner->runDisableHook('testmod7'));
    }

    #[Test]
    public function run_setup_hooks_runs_install_for_new_modules(): void
    {
        $moduleDir = $this->tempDir . '/testmod8';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onInstall" => fn() => null, "beforeSetup" => fn() => null];');
        
        $module = new ModuleDefinition(['name' => 'testmod8', 'version' => '1.0.0'], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $results = $runner->runSetupHooks();
        
        $this->assertContains('testmod8', $results['installed']);
        $this->assertContains('testmod8', $results['beforeSetup']);
    }

    #[Test]
    public function run_setup_hooks_runs_upgrade_for_updated_modules(): void
    {
        // Pre-install the module at version 1.0.0
        file_put_contents($this->statePath, "<?php return ['installed' => ['testmod9' => '1.0.0']];");
        
        $moduleDir = $this->tempDir . '/testmod9';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["onUpgrade" => fn($from) => null];');
        
        $module = new ModuleDefinition(['name' => 'testmod9', 'version' => '2.0.0'], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $results = $runner->runSetupHooks();
        
        $this->assertNotEmpty($results['upgraded']);
    }

    #[Test]
    public function run_after_setup_hooks_executes_hooks(): void
    {
        $moduleDir = $this->tempDir . '/testmod10';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/hooks.php', '<?php return ["afterSetup" => fn() => null];');
        
        $module = new ModuleDefinition(['name' => 'testmod10'], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $runner = new ModuleHookRunner($registry, $this->statePath);
        
        $results = $runner->runAfterSetupHooks();
        
        $this->assertContains('testmod10', $results);
    }
}
