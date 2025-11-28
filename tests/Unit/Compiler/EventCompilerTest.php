<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use App\Core\Compiler\EventCompiler;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventCompilerTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/event_compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        $this->cachePath = $this->tempDir . '/var/cache/events.php';
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
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $compiler->compile();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function compile_loads_module_events(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/events.php',
            "<?php return ['TestEvent' => ['TestListener']];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'events' => 'events.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertArrayHasKey('TestEvent', $result);
    }

    #[Test]
    public function compile_normalizes_string_listener(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/events.php',
            "<?php return ['TestEvent' => ['ListenerClass']];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'events' => 'events.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('ListenerClass', $result['TestEvent'][0]['class']);
        $this->assertSame('handle', $result['TestEvent'][0]['method']);
        $this->assertSame(0, $result['TestEvent'][0]['priority']);
    }

    #[Test]
    public function compile_normalizes_array_listener(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/events.php',
            "<?php return ['TestEvent' => [['ListenerClass', 'onEvent', 10]]];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'events' => 'events.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('ListenerClass', $result['TestEvent'][0]['class']);
        $this->assertSame('onEvent', $result['TestEvent'][0]['method']);
        $this->assertSame(10, $result['TestEvent'][0]['priority']);
    }

    #[Test]
    public function compile_sorts_by_priority(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/events.php',
            "<?php return ['TestEvent' => [['Low', 'handle', 0], ['High', 'handle', 10]]];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'events' => 'events.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('High', $result['TestEvent'][0]['class']);
        $this->assertSame('Low', $result['TestEvent'][1]['class']);
    }

    #[Test]
    public function get_stats_returns_listener_counts(): void
    {
        file_put_contents(
            $this->cachePath,
            "<?php return ['EventA' => [1, 2], 'EventB' => [1]];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        
        $stats = $compiler->getStats();
        
        $this->assertSame(2, $stats['EventA']);
        $this->assertSame(1, $stats['EventB']);
    }

    #[Test]
    public function uses_default_cache_path(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new EventCompiler($this->tempDir, null, $registry);
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function throws_on_invalid_listener_format(): void
    {
        $moduleDir = $this->tempDir . '/modules/invalid';
        mkdir($moduleDir, 0755, true);
        // Create an events file with invalid listener format (not string or array)
        file_put_contents(
            $moduleDir . '/events.php',
            "<?php return ['TestEvent' => [123]];"  // Integer is invalid
        );
        
        $module = new ModuleDefinition([
            'name' => 'invalid',
            'events' => 'events.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid listener format');
        
        $compiler = new EventCompiler($this->tempDir, $this->cachePath, $registry);
        $compiler->compile();
    }
}
