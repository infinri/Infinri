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
namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\ModuleMakeCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleMakeCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_module_make(): void
    {
        $command = new ModuleMakeCommand();
        
        $this->assertSame('module:make', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new ModuleMakeCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function handle_without_name_shows_error(): void
    {
        $command = new ModuleMakeCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function handle_creates_module_structure(): void
    {
        $command = new ModuleMakeCommand();
        $name = 'testmod' . uniqid();
        
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Module', $output);
        
        // Clean up
        $this->cleanupModule($name);
    }

    #[Test]
    public function handle_creates_all_required_files(): void
    {
        $command = new ModuleMakeCommand();
        $name = 'testfiles' . uniqid();
        
        ob_start();
        $command->handle([$name]);
        ob_get_clean();
        
        $rootDir = dirname(__DIR__, 4);
        $modulePath = $rootDir . '/app/modules/' . $name;
        
        $this->assertFileExists($modulePath . '/module.php');
        $this->assertFileExists($modulePath . '/index.php');
        $this->assertFileExists($modulePath . '/config.php');
        $this->assertFileExists($modulePath . '/events.php');
        $this->assertFileExists($modulePath . '/hooks.php');
        
        // Clean up
        $this->cleanupModule($name);
    }

    #[Test]
    public function handle_creates_directories(): void
    {
        $command = new ModuleMakeCommand();
        $name = 'testdirs' . uniqid();
        
        ob_start();
        $command->handle([$name]);
        ob_get_clean();
        
        $rootDir = dirname(__DIR__, 4);
        $modulePath = $rootDir . '/app/modules/' . $name;
        
        $this->assertDirectoryExists($modulePath);
        $this->assertDirectoryExists($modulePath . '/Providers');
        $this->assertDirectoryExists($modulePath . '/Controllers');
        $this->assertDirectoryExists($modulePath . '/Models');
        
        // Clean up
        $this->cleanupModule($name);
    }

    #[Test]
    public function handle_creates_service_provider(): void
    {
        $command = new ModuleMakeCommand();
        $name = 'testprov' . uniqid();
        $className = ucfirst($name);
        
        ob_start();
        $command->handle([$name]);
        ob_get_clean();
        
        $rootDir = dirname(__DIR__, 4);
        $providerPath = $rootDir . '/app/modules/' . $name . '/Providers/' . $className . 'ServiceProvider.php';
        
        $this->assertFileExists($providerPath);
        $content = file_get_contents($providerPath);
        $this->assertStringContainsString('ServiceProvider', $content);
        
        // Clean up
        $this->cleanupModule($name);
    }

    #[Test]
    public function handle_error_for_existing_module(): void
    {
        $command = new ModuleMakeCommand();
        $name = 'testexist' . uniqid();
        
        // Create module first
        ob_start();
        $command->handle([$name]);
        ob_get_clean();
        
        // Try to create again
        ob_start();
        $result = $command->handle([$name]);
        $output = ob_get_clean();
        
        $this->assertSame(1, $result);
        $this->assertStringContainsString('already exists', $output);
        
        // Clean up
        $this->cleanupModule($name);
    }

    private function cleanupModule(string $name): void
    {
        $rootDir = dirname(__DIR__, 4);
        $modulePath = $rootDir . '/app/modules/' . $name;
        
        if (is_dir($modulePath)) {
            $this->recursiveDelete($modulePath);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->recursiveDelete($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
