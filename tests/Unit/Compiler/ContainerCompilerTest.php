<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use App\Core\Compiler\ContainerCompiler;
use App\Core\Module\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContainerCompilerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/container_compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        mkdir($this->tempDir . '/modules', 0755, true);
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
    public function compile_returns_array(): void
    {
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('providers', $result);
        $this->assertArrayHasKey('deferred', $result);
        $this->assertArrayHasKey('provides', $result);
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        $compiler->compile();
        
        $this->assertFileExists($this->tempDir . '/var/cache/container.php');
    }

    #[Test]
    public function get_stats_returns_provider_counts(): void
    {
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        $compiler->compile();
        
        $stats = $compiler->getStats();
        
        $this->assertArrayHasKey('total_providers', $stats);
        $this->assertArrayHasKey('deferred_providers', $stats);
        $this->assertArrayHasKey('deferred_services', $stats);
    }

    #[Test]
    public function load_returns_compiled_data(): void
    {
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        $compiler->compile();
        
        $data = $compiler->load();
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('providers', $data);
    }

    #[Test]
    public function clear_removes_cache(): void
    {
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        $compiler->compile();
        
        $compiler->clear();
        
        $this->assertFileDoesNotExist($this->tempDir . '/var/cache/container.php');
    }

    #[Test]
    public function compile_with_module_providers(): void
    {
        // Create a test module with a provider
        $modulePath = $this->tempDir . '/modules/testmodule';
        mkdir($modulePath, 0755, true);
        
        // Create module.php
        file_put_contents($modulePath . '/module.php', '<?php return [
            "name" => "TestModule",
            "version" => "1.0.0",
            "enabled" => true,
            "providers" => [],
        ];');
        
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('providers', $result);
    }

    #[Test]
    public function compile_handles_nonexistent_provider_class(): void
    {
        // Create a test module with a nonexistent provider
        $modulePath = $this->tempDir . '/modules/badprov';
        mkdir($modulePath, 0755, true);
        
        file_put_contents($modulePath . '/module.php', '<?php return [
            "name" => "BadProvider",
            "version" => "1.0.0",
            "enabled" => true,
            "providers" => ["NonExistentProviderClass"],
        ];');
        
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        // Should not throw
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function compile_detects_deferred_providers(): void
    {
        // Create a deferred provider class
        $providerCode = <<<'PHP'
<?php
namespace Tests\Fixtures\Providers;

class DeferredTestProvider {
    public function isDeferred(): bool { return true; }
    public function provides(): array { return ['test.service']; }
}
PHP;
        // Make sure the namespace directory exists
        $fixturesDir = $this->tempDir . '/tests/Fixtures/Providers';
        mkdir($fixturesDir, 0755, true);
        file_put_contents($fixturesDir . '/DeferredTestProvider.php', $providerCode);
        
        // Load the provider class
        require_once $fixturesDir . '/DeferredTestProvider.php';
        
        // Create a module that uses this provider
        $modulePath = $this->tempDir . '/modules/deferredmod';
        mkdir($modulePath, 0755, true);
        
        file_put_contents($modulePath . '/module.php', '<?php return [
            "name" => "DeferredModule",
            "version" => "1.0.0",
            "enabled" => true,
            "providers" => ["Tests\\\\Fixtures\\\\Providers\\\\DeferredTestProvider"],
        ];');
        
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('deferred', $result);
        $this->assertArrayHasKey('provides', $result);
    }

    #[Test]
    public function compile_handles_provider_with_no_deferred_method(): void
    {
        // Create a simple provider class with no deferred method
        $providerCode = <<<'PHP'
<?php
namespace Tests\Fixtures\Providers;

class SimpleTestProvider {
    public function register(): void {}
}
PHP;
        $fixturesDir = $this->tempDir . '/tests/Fixtures/Providers';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
        }
        file_put_contents($fixturesDir . '/SimpleTestProvider.php', $providerCode);
        
        require_once $fixturesDir . '/SimpleTestProvider.php';
        
        $modulePath = $this->tempDir . '/modules/simplemod';
        mkdir($modulePath, 0755, true);
        
        file_put_contents($modulePath . '/module.php', '<?php return [
            "name" => "SimpleModule",
            "version" => "1.0.0",
            "enabled" => true,
            "providers" => ["Tests\\\\Fixtures\\\\Providers\\\\SimpleTestProvider"],
        ];');
        
        $registry = new ModuleRegistry($this->tempDir . '/modules', $this->tempDir . '/var/cache/modules.php');
        $compiler = new ContainerCompiler($this->tempDir, null, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
        $this->assertContains([
            'class' => 'Tests\\Fixtures\\Providers\\SimpleTestProvider',
            'module' => 'SimpleModule',
        ], $result['providers']);
    }
}
