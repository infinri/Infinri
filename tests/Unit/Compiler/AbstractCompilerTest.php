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

use App\Core\Compiler\AbstractCompiler;
use App\Core\Module\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractCompilerTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->cachePath = $this->tempDir . '/cache.php';
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
    public function is_cached_returns_false_when_no_cache(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        
        $this->assertFalse($compiler->isCached());
    }

    #[Test]
    public function is_cached_returns_true_after_compile(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $compiler->compile();
        
        $this->assertTrue($compiler->isCached());
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $compiler->compile();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function compile_returns_data(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $data = $compiler->compile();
        
        $this->assertSame(['test' => 'data'], $data);
    }

    #[Test]
    public function load_compiles_when_not_cached(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $data = $compiler->load();
        
        $this->assertSame(['test' => 'data'], $data);
    }

    #[Test]
    public function load_uses_cache_when_available(): void
    {
        // Create cache with different data
        file_put_contents($this->cachePath, "<?php return ['cached' => true];");
        
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $data = $compiler->load();
        
        $this->assertSame(['cached' => true], $data);
    }

    #[Test]
    public function clear_removes_cache_file(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        $compiler->compile();
        $this->assertFileExists($this->cachePath);
        
        $compiler->clear();
        
        $this->assertFileDoesNotExist($this->cachePath);
    }

    #[Test]
    public function clear_handles_missing_cache(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        
        // Should not throw
        $compiler->clear();
        
        $this->assertFileDoesNotExist($this->cachePath);
    }

    #[Test]
    public function get_cache_path_returns_path(): void
    {
        $compiler = new TestCompiler($this->tempDir, $this->cachePath);
        
        $this->assertSame($this->cachePath, $compiler->getCachePath());
    }

    #[Test]
    public function constructor_accepts_custom_registry(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $compiler = new TestCompiler($this->tempDir, $this->cachePath, $registry);
        
        $this->assertInstanceOf(TestCompiler::class, $compiler);
    }
}

/**
 * Concrete implementation for testing
 */
class TestCompiler extends AbstractCompiler
{
    public function compile(): array
    {
        $data = ['test' => 'data'];
        $this->saveToCache($data, 'Test Cache');
        return $data;
    }

    protected function getDefaultCachePath(): string
    {
        return $this->basePath . '/cache/test.php';
    }
}
