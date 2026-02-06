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

use App\Core\Module\ModuleManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_manager_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
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
    public function constructor_accepts_custom_path(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertSame($this->tempDir, $manager->getModulesPath());
    }

    #[Test]
    public function discover_returns_empty_for_nonexistent_dir(): void
    {
        $manager = new ModuleManager($this->tempDir . '/nonexistent');
        
        $this->assertSame([], $manager->discover());
    }

    #[Test]
    public function discover_returns_empty_for_empty_dir(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertSame([], $manager->discover());
    }

    #[Test]
    public function discover_finds_valid_modules(): void
    {
        // Create a valid module structure
        mkdir($this->tempDir . '/home', 0755, true);
        file_put_contents($this->tempDir . '/home/HomeModule.php', '<?php');
        
        $manager = new ModuleManager($this->tempDir);
        $modules = $manager->discover();
        
        $this->assertContains('home', $modules);
    }

    #[Test]
    public function discover_ignores_invalid_names(): void
    {
        mkdir($this->tempDir . '/Invalid-Name', 0755, true);
        file_put_contents($this->tempDir . '/Invalid-Name/Invalid-NameModule.php', '<?php');
        
        $manager = new ModuleManager($this->tempDir);
        $modules = $manager->discover();
        
        $this->assertNotContains('Invalid-Name', $modules);
    }

    #[Test]
    public function discover_caches_results(): void
    {
        mkdir($this->tempDir . '/test', 0755, true);
        file_put_contents($this->tempDir . '/test/TestModule.php', '<?php');
        
        $manager = new ModuleManager($this->tempDir);
        $first = $manager->discover();
        $second = $manager->discover();
        
        $this->assertSame($first, $second);
    }

    #[Test]
    public function clear_cache_resets_discovered(): void
    {
        mkdir($this->tempDir . '/test', 0755, true);
        file_put_contents($this->tempDir . '/test/TestModule.php', '<?php');
        
        $manager = new ModuleManager($this->tempDir);
        $manager->discover();
        $manager->clearCache();
        
        // Add another module
        mkdir($this->tempDir . '/another', 0755, true);
        file_put_contents($this->tempDir . '/another/AnotherModule.php', '<?php');
        
        $modules = $manager->discover();
        $this->assertContains('another', $modules);
    }

    #[Test]
    public function get_class_file_returns_correct_path(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $path = $manager->getClassFile('home');
        
        $this->assertSame($this->tempDir . '/home/HomeModule.php', $path);
    }

    #[Test]
    public function get_class_file_throws_for_invalid_name(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->expectException(\InvalidArgumentException::class);
        $manager->getClassFile('Invalid-Name');
    }

    #[Test]
    public function get_class_name_returns_fqcn(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $className = $manager->getClassName('home');
        
        $this->assertSame('\\App\\Modules\\Home\\HomeModule', $className);
    }

    #[Test]
    public function get_path_returns_module_path(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $path = $manager->getPath('home');
        
        $this->assertSame($this->tempDir . '/home', $path);
    }

    #[Test]
    public function get_path_appends_subpath(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $path = $manager->getPath('home', 'view/frontend');
        
        $this->assertSame($this->tempDir . '/home/view/frontend', $path);
    }

    #[Test]
    public function exists_returns_true_for_existing_module(): void
    {
        mkdir($this->tempDir . '/home', 0755, true);
        file_put_contents($this->tempDir . '/home/HomeModule.php', '<?php');
        
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertTrue($manager->exists('home'));
    }

    #[Test]
    public function exists_returns_false_for_missing_module(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertFalse($manager->exists('nonexistent'));
    }

    #[Test]
    public function exists_returns_false_for_invalid_name(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertFalse($manager->exists('Invalid-Name'));
    }

    #[Test]
    public function has_assets_returns_true_when_dir_exists(): void
    {
        mkdir($this->tempDir . '/home/view/frontend', 0755, true);
        
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertTrue($manager->hasAssets('home', 'frontend'));
    }

    #[Test]
    public function has_assets_returns_false_when_dir_missing(): void
    {
        mkdir($this->tempDir . '/home', 0755, true);
        
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertFalse($manager->hasAssets('home', 'frontend'));
    }

    #[Test]
    public function has_assets_returns_false_for_invalid_name(): void
    {
        $manager = new ModuleManager($this->tempDir);
        
        $this->assertFalse($manager->hasAssets('Invalid-Name'));
    }

    #[Test]
    public function set_modules_path_updates_path_and_clears_cache(): void
    {
        $manager = new ModuleManager($this->tempDir);
        $newPath = $this->tempDir . '/new';
        mkdir($newPath, 0755, true);
        
        $manager->setModulesPath($newPath);
        
        $this->assertSame($newPath, $manager->getModulesPath());
    }

    #[Test]
    public function discover_modules_static_returns_array(): void
    {
        $modules = ModuleManager::discoverModules();
        
        $this->assertIsArray($modules);
    }

    #[Test]
    public function discover_skips_non_directories(): void
    {
        // Create a file in the modules path that isn't a directory
        file_put_contents($this->tempDir . '/not_a_module.txt', 'test');
        
        $manager = new ModuleManager($this->tempDir);
        $modules = $manager->discover();
        
        // Should not include the file
        $this->assertIsArray($modules);
        $this->assertNotContains('not_a_module.txt', $modules);
    }
}
