<?php

declare(strict_types=1);


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

use App\Core\Application;
use App\Core\Module\ModuleLoader;
use App\Core\Module\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleLoaderTest extends TestCase
{
    private string $tempDir;
    private Application $app;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_loader_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/logs', 0755, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=Test\n");
        
        // Reset singleton
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->app = new Application($this->tempDir);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
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
    public function constructor_accepts_application(): void
    {
        $loader = new ModuleLoader($this->app);
        
        $this->assertInstanceOf(ModuleLoader::class, $loader);
    }

    #[Test]
    public function constructor_accepts_custom_registry(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $loader = new ModuleLoader($this->app, $registry);
        
        $this->assertInstanceOf(ModuleLoader::class, $loader);
    }

    #[Test]
    public function is_loaded_returns_false_initially(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        $loader = new ModuleLoader($this->app, $registry);
        
        $this->assertFalse($loader->isLoaded());
    }

    #[Test]
    public function get_registry_returns_registry(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $loader = new ModuleLoader($this->app, $registry);
        
        $this->assertSame($registry, $loader->getRegistry());
    }

    #[Test]
    public function get_commands_returns_empty_array_initially(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $loader = new ModuleLoader($this->app, $registry);
        
        $this->assertSame([], $loader->getCommands());
    }

    #[Test]
    public function load_sets_loaded_flag(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $loader = new ModuleLoader($this->app, $registry);
        $loader->load();
        
        $this->assertTrue($loader->isLoaded());
    }

    #[Test]
    public function load_only_runs_once(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $loader = new ModuleLoader($this->app, $registry);
        $loader->load();
        $loader->load(); // Second call should be skipped due to loaded flag
        
        $this->assertTrue($loader->isLoaded());
    }

    #[Test]
    public function get_modules_triggers_load(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->method('getEnabled')->willReturn([]);
        
        $loader = new ModuleLoader($this->app, $registry);
        $loader->getModules();
        
        $this->assertTrue($loader->isLoaded());
    }

    #[Test]
    public function get_module_delegates_to_registry(): void
    {
        $registry = $this->createMock(ModuleRegistry::class);
        $registry->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn(null);
        
        $loader = new ModuleLoader($this->app, $registry);
        $result = $loader->getModule('test');
        
        $this->assertNull($result);
    }
}
