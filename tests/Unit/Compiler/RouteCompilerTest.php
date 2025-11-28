<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use App\Core\Compiler\RouteCompiler;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteCompilerTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/route_compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        $this->cachePath = $this->tempDir . '/var/cache/routes.php';
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
        
        $compiler = new RouteCompiler($this->tempDir, $this->cachePath, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new RouteCompiler($this->tempDir, $this->cachePath, $registry);
        $compiler->compile();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function compile_loads_module_routes(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/routes.php',
            "<?php return [['method' => 'GET', 'path' => '/test']];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'routes' => 'routes.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new RouteCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertCount(1, $result);
        $this->assertSame('test', $result[0]['module']);
    }

    #[Test]
    public function get_stats_returns_route_counts(): void
    {
        file_put_contents(
            $this->cachePath,
            "<?php return [['module' => 'a'], ['module' => 'a'], ['module' => 'b']];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $compiler = new RouteCompiler($this->tempDir, $this->cachePath, $registry);
        
        $stats = $compiler->getStats();
        
        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['by_module']['a']);
        $this->assertSame(1, $stats['by_module']['b']);
    }

    #[Test]
    public function uses_default_cache_path(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new RouteCompiler($this->tempDir, null, $registry);
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }
}
