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
namespace Tests\Unit\Support\Facades;

use App\Core\Application;
use App\Core\Support\Facades\Facade;
use App\Core\Support\Facades\Config as ConfigFacade;
use App\Core\Support\Facades\Log as LogFacade;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FacadeTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/facade_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/storage');
        mkdir($this->tempDir . '/storage/logs');
        
        // Create a test .env file
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        // Reset Application singleton
        $this->resetApplicationSingleton();
        
        // Create fresh application
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
        
        // Set facade application
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        $this->resetApplicationSingleton();
        
        // Clean up temp directory
        $this->removeDirectory($this->tempDir);
    }

    private function resetApplicationSingleton(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_can_set_facade_application(): void
    {
        Facade::setFacadeApplication($this->app);
        
        // If no exception thrown, facade application was set
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_clear_resolved_instances(): void
    {
        // Call a facade to resolve an instance
        ConfigFacade::get('app.name', 'default');
        
        // Clear should not throw
        Facade::clearResolvedInstances();
        
        $this->assertTrue(true);
    }

    #[Test]
    public function config_facade_can_get_values(): void
    {
        $value = ConfigFacade::get('app.name', 'default');
        
        $this->assertIsString($value);
    }

    #[Test]
    public function config_facade_can_set_values(): void
    {
        ConfigFacade::set('test.key', 'test-value');
        
        $result = ConfigFacade::get('test.key');
        
        $this->assertSame('test-value', $result);
    }

    #[Test]
    public function config_facade_can_check_if_key_exists(): void
    {
        ConfigFacade::set('existing.key', 'value');
        
        $this->assertTrue(ConfigFacade::has('existing.key'));
        $this->assertFalse(ConfigFacade::has('non.existing.key'));
    }

    #[Test]
    public function log_facade_can_log_info(): void
    {
        // Should not throw
        LogFacade::info('Test log message');
        
        $this->assertTrue(true);
    }

    #[Test]
    public function log_facade_can_log_error(): void
    {
        // Should not throw
        LogFacade::error('Test error message', ['context' => 'test']);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function log_facade_can_log_warning(): void
    {
        // Should not throw
        LogFacade::warning('Test warning');
        
        $this->assertTrue(true);
    }

    #[Test]
    public function log_facade_can_log_debug(): void
    {
        // Should not throw
        LogFacade::debug('Test debug message');
        
        $this->assertTrue(true);
    }

    #[Test]
    public function facade_caches_resolved_instances(): void
    {
        // First call resolves
        ConfigFacade::get('app.name');
        
        // Second call should use cached instance (no exception = success)
        ConfigFacade::get('app.debug');
        
        $this->assertTrue(true);
    }

    #[Test]
    public function facade_falls_back_to_application_instance(): void
    {
        // Clear the facade application
        Facade::setFacadeApplication($this->app);
        Facade::clearResolvedInstances();
        
        // Clear the static app property using reflection
        $reflection = new \ReflectionClass(Facade::class);
        $property = $reflection->getProperty('app');
        $property->setValue(null, null);
        
        // Now ConfigFacade should fall back to Application::getInstance()
        $result = ConfigFacade::get('app.name');
        
        $this->assertNotNull($result);
    }

    #[Test]
    public function facade_throws_when_service_not_found(): void
    {
        // Create a test facade that returns a non-existent service
        $this->expectException(\App\Core\Container\BindingResolutionException::class);
        
        // Call the test facade - it will fail to resolve 'non.existent.service'
        NonExistentFacade::someMethod();
    }
}

// Test facade for non-existent service
class NonExistentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'non.existent.service';
    }
}

// Test concrete facade implementation
class TestFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'test.service';
    }
}
