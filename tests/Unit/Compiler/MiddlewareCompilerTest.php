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
namespace Tests\Unit\Compiler;

use App\Core\Compiler\MiddlewareCompiler;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareCompilerTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/middleware_compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        $this->cachePath = $this->tempDir . '/var/cache/middleware.php';
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
    public function compile_returns_structured_array(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        
        $result = $compiler->compile();
        
        $this->assertArrayHasKey('global', $result);
        $this->assertArrayHasKey('web', $result);
        $this->assertArrayHasKey('api', $result);
        $this->assertArrayHasKey('aliases', $result);
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        $compiler->compile();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function compile_loads_app_middleware(): void
    {
        mkdir($this->tempDir . '/app/Http', 0755, true);
        file_put_contents(
            $this->tempDir . '/app/Http/middleware.php',
            "<?php return ['global' => ['AppMiddleware']];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertContains('AppMiddleware', $result['global']);
    }

    #[Test]
    public function compile_loads_module_middleware(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/middleware.php',
            "<?php return ['web' => ['ModuleMiddleware']];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertContains('ModuleMiddleware', $result['web']);
    }

    #[Test]
    public function compile_sorts_by_priority(): void
    {
        mkdir($this->tempDir . '/app/Http', 0755, true);
        file_put_contents(
            $this->tempDir . '/app/Http/middleware.php',
            "<?php return ['global' => ['Low' => ['priority' => 0], 'High' => ['priority' => 10]]];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('High', $result['global'][0]);
        $this->assertSame('Low', $result['global'][1]);
    }

    #[Test]
    public function compile_merges_aliases(): void
    {
        mkdir($this->tempDir . '/app/Http', 0755, true);
        file_put_contents(
            $this->tempDir . '/app/Http/middleware.php',
            "<?php return ['aliases' => ['auth' => 'AuthMiddleware']];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('AuthMiddleware', $result['aliases']['auth']);
    }

    #[Test]
    public function get_stats_returns_counts(): void
    {
        file_put_contents(
            $this->cachePath,
            "<?php return ['global' => [1, 2], 'web' => [1], 'api' => [], 'aliases' => ['a' => 'b']];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $compiler = new MiddlewareCompiler($this->tempDir, $this->cachePath, $registry);
        
        $stats = $compiler->getStats();
        
        $this->assertSame(2, $stats['global']);
        $this->assertSame(1, $stats['web']);
        $this->assertSame(0, $stats['api']);
        $this->assertSame(1, $stats['aliases']);
    }

    #[Test]
    public function uses_default_cache_path_when_not_provided(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        // Pass null for cache path to trigger getDefaultCachePath
        $compiler = new MiddlewareCompiler($this->tempDir, null, $registry);
        
        // Compile should work using default path
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }
}
