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

use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleRegistryTest extends TestCase
{
    private string $tempDir;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_registry_test_' . uniqid();
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

    private function createModule(string $name, array $config = []): void
    {
        $path = $this->tempDir . '/' . $name;
        mkdir($path, 0755, true);
        
        $data = array_merge([
            'name' => $name,
            'version' => '1.0.0',
            'enabled' => true,
            'dependencies' => [],
        ], $config);
        
        $content = "<?php\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($path . '/module.php', $content);
    }

    #[Test]
    public function constructor_accepts_custom_paths(): void
    {
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertInstanceOf(ModuleRegistry::class, $registry);
    }

    #[Test]
    public function scan_finds_modules_with_module_php(): void
    {
        $this->createModule('home');
        $this->createModule('contact');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->scan();
        
        $this->assertTrue($registry->has('home'));
        $this->assertTrue($registry->has('contact'));
    }

    #[Test]
    public function scan_ignores_directories_without_module_php(): void
    {
        mkdir($this->tempDir . '/invalid', 0755, true);
        file_put_contents($this->tempDir . '/invalid/readme.txt', 'test');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->scan();
        
        $this->assertFalse($registry->has('invalid'));
    }

    #[Test]
    public function scan_handles_legacy_module_class(): void
    {
        $path = $this->tempDir . '/legacy';
        mkdir($path, 0755, true);
        file_put_contents($path . '/LegacyModule.php', '<?php class LegacyModule {}');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->scan();
        
        $this->assertTrue($registry->has('legacy'));
    }

    #[Test]
    public function all_returns_all_modules(): void
    {
        $this->createModule('home');
        $this->createModule('contact');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $modules = $registry->all();
        
        $this->assertCount(2, $modules);
    }

    #[Test]
    public function get_returns_module_definition(): void
    {
        $this->createModule('home', ['version' => '2.0.0']);
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $module = $registry->get('home');
        
        $this->assertInstanceOf(ModuleDefinition::class, $module);
        $this->assertSame('2.0.0', $module->version);
    }

    #[Test]
    public function get_returns_null_for_unknown(): void
    {
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertNull($registry->get('unknown'));
    }

    #[Test]
    public function has_returns_true_for_existing_module(): void
    {
        $this->createModule('home');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertTrue($registry->has('home'));
    }

    #[Test]
    public function has_returns_false_for_unknown(): void
    {
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertFalse($registry->has('unknown'));
    }

    #[Test]
    public function get_enabled_returns_only_enabled_modules(): void
    {
        $this->createModule('home', ['enabled' => true]);
        $this->createModule('contact', ['enabled' => false]);
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $enabled = $registry->getEnabled();
        
        $this->assertCount(1, $enabled);
        $this->assertSame('home', $enabled[0]->name);
    }

    #[Test]
    public function resolve_load_order_sorts_by_dependencies(): void
    {
        $this->createModule('base', ['dependencies' => []]);
        $this->createModule('child', ['dependencies' => ['base']]);
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->load();
        
        $order = $registry->getLoadOrder();
        $baseIndex = array_search('base', $order);
        $childIndex = array_search('child', $order);
        
        $this->assertLessThan($childIndex, $baseIndex);
    }

    #[Test]
    public function resolve_load_order_throws_on_missing_dependency(): void
    {
        $this->createModule('child', ['dependencies' => ['nonexistent']]);
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->scan();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing module');
        
        $registry->resolveLoadOrder();
    }

    #[Test]
    public function resolve_load_order_throws_on_circular_dependency(): void
    {
        $this->createModule('a', ['dependencies' => ['b']]);
        $this->createModule('b', ['dependencies' => ['a']]);
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->scan();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency');
        
        $registry->resolveLoadOrder();
    }

    #[Test]
    public function load_caches_results(): void
    {
        $this->createModule('home');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->load();
        
        $this->assertFileExists($this->cachePath);
    }

    #[Test]
    public function load_uses_cache_on_second_call(): void
    {
        $this->createModule('home');
        
        $registry1 = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry1->load();
        
        // Create new registry instance - should load from cache
        $registry2 = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry2->load();
        
        $this->assertTrue($registry2->has('home'));
    }

    #[Test]
    public function clear_cache_removes_cache_file(): void
    {
        $this->createModule('home');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->load();
        $this->assertFileExists($this->cachePath);
        
        $registry->clearCache();
        
        $this->assertFileDoesNotExist($this->cachePath);
    }

    #[Test]
    public function rebuild_rescans_modules(): void
    {
        $this->createModule('home');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $registry->load();
        
        // Add new module
        $this->createModule('new_module');
        
        $registry->rebuild();
        
        $this->assertTrue($registry->has('new_module'));
    }

    #[Test]
    public function enable_returns_false_for_unknown_module(): void
    {
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertFalse($registry->enable('unknown'));
    }

    #[Test]
    public function disable_returns_false_for_unknown_module(): void
    {
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        
        $this->assertFalse($registry->disable('unknown'));
    }

    #[Test]
    public function scan_handles_nonexistent_directory(): void
    {
        $registry = new ModuleRegistry('/nonexistent/path', $this->cachePath);
        $registry->scan();
        
        $this->assertSame([], $registry->all());
    }

    #[Test]
    public function get_load_order_returns_array(): void
    {
        $this->createModule('home');
        
        $registry = new ModuleRegistry($this->tempDir, $this->cachePath);
        $order = $registry->getLoadOrder();
        
        $this->assertIsArray($order);
        $this->assertContains('home', $order);
    }
}
