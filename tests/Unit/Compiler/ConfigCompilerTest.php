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

use App\Core\Compiler\ConfigCompiler;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigCompilerTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/config_compiler_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        $this->cachePath = $this->tempDir . '/var/cache/config.php';
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
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function compile_creates_cache_file(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        $compiler->compile();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function compile_loads_app_config(): void
    {
        // Create app config
        mkdir($this->tempDir . '/app', 0755, true);
        file_put_contents(
            $this->tempDir . '/app/config.php',
            "<?php return ['app_name' => 'Test'];"
        );
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('Test', $result['app_name']);
    }

    #[Test]
    public function compile_loads_module_configs(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/config.php',
            "<?php return ['key' => 'value'];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'config' => 'config.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('value', $result['modules']['test']['key']);
    }

    #[Test]
    public function compile_handles_global_config(): void
    {
        $moduleDir = $this->tempDir . '/modules/test';
        mkdir($moduleDir, 0755, true);
        file_put_contents(
            $moduleDir . '/config.php',
            "<?php return ['_global' => ['global_key' => 'global_value']];"
        );
        
        $module = new ModuleDefinition([
            'name' => 'test',
            'config' => 'config.php',
        ], $moduleDir);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module]);
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        $this->assertSame('global_value', $result['global_key']);
    }

    #[Test]
    public function load_uses_cache_when_available(): void
    {
        file_put_contents($this->cachePath, "<?php return ['cached' => true];");
        
        $registry = $this->createMock(ModuleRegistry::class);
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        
        $result = $compiler->load();
        
        $this->assertTrue($result['cached']);
    }

    #[Test]
    public function uses_default_cache_path(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $compiler = new ConfigCompiler($this->tempDir, null, $registry);
        $result = $compiler->compile();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function compile_deep_merges_nested_arrays(): void
    {
        // First module with nested config
        $moduleDir1 = $this->tempDir . '/modules/first';
        mkdir($moduleDir1, 0755, true);
        file_put_contents(
            $moduleDir1 . '/config.php',
            "<?php return ['_global' => ['nested' => ['key1' => 'value1', 'key2' => 'original']]];"
        );
        
        // Second module overriding part of nested config
        $moduleDir2 = $this->tempDir . '/modules/second';
        mkdir($moduleDir2, 0755, true);
        file_put_contents(
            $moduleDir2 . '/config.php',
            "<?php return ['_global' => ['nested' => ['key2' => 'overridden', 'key3' => 'value3']]];"
        );
        
        $module1 = new ModuleDefinition(['name' => 'first', 'config' => 'config.php'], $moduleDir1);
        $module2 = new ModuleDefinition(['name' => 'second', 'config' => 'config.php'], $moduleDir2);
        
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([$module1, $module2]);
        
        $compiler = new ConfigCompiler($this->tempDir, $this->cachePath, $registry);
        $result = $compiler->compile();
        
        // Should have deep merged the nested arrays
        $this->assertSame('value1', $result['nested']['key1']);
        $this->assertSame('overridden', $result['nested']['key2']);
        $this->assertSame('value3', $result['nested']['key3']);
    }
}
