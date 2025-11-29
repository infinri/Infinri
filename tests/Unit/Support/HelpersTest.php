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
namespace Tests\Unit\Support;

use App\Core\Application;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/helpers_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/storage');
        mkdir($this->tempDir . '/storage/logs');
        
        // Create test .env
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        // Reset singleton
        $this->resetApplicationSingleton();
        
        // Create and bootstrap application
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
    }

    protected function tearDown(): void
    {
        $this->resetApplicationSingleton();
        $this->removeDirectory($this->tempDir);
    }

    private function resetApplicationSingleton(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
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

    // env() helper tests

    #[Test]
    public function env_returns_value_from_environment(): void
    {
        putenv('TEST_ENV_VAR=test_value');
        
        $result = env('TEST_ENV_VAR');
        
        $this->assertSame('test_value', $result);
        
        putenv('TEST_ENV_VAR'); // Clean up
    }

    #[Test]
    public function env_returns_default_when_not_set(): void
    {
        putenv('NON_EXISTENT_VAR'); // Ensure not set
        
        $result = env('NON_EXISTENT_VAR', 'default_value');
        
        $this->assertSame('default_value', $result);
    }

    #[Test]
    public function env_converts_empty_string(): void
    {
        putenv('TEST_EMPTY=empty');
        $this->assertSame('', env('TEST_EMPTY'));
        
        putenv('TEST_EMPTY=(empty)');
        $this->assertSame('', env('TEST_EMPTY'));
        
        putenv('TEST_EMPTY');
    }

    #[Test]
    public function env_converts_null_string(): void
    {
        putenv('TEST_NULL=null');
        $this->assertNull(env('TEST_NULL'));
        
        putenv('TEST_NULL=(null)');
        $this->assertNull(env('TEST_NULL'));
        
        putenv('TEST_NULL');
    }

    #[Test]
    public function env_converts_parenthesized_true(): void
    {
        putenv('TEST_BOOL=(true)');
        $this->assertTrue(env('TEST_BOOL'));
        
        putenv('TEST_BOOL');
    }

    #[Test]
    public function env_converts_parenthesized_false(): void
    {
        putenv('TEST_BOOL=(false)');
        $this->assertFalse(env('TEST_BOOL'));
        
        putenv('TEST_BOOL');
    }

    #[Test]
    public function env_converts_on_off(): void
    {
        putenv('TEST_BOOL=on');
        $this->assertTrue(env('TEST_BOOL'));
        
        putenv('TEST_BOOL=off');
        $this->assertFalse(env('TEST_BOOL'));
        
        putenv('TEST_BOOL');
    }

    // app() helper tests

    #[Test]
    public function app_returns_application_instance_when_no_argument(): void
    {
        $result = app();
        
        $this->assertInstanceOf(Application::class, $result);
    }

    #[Test]
    public function app_resolves_abstract_from_container(): void
    {
        $result = app(ConfigInterface::class);
        
        $this->assertInstanceOf(ConfigInterface::class, $result);
    }

    #[Test]
    public function app_can_pass_parameters_when_resolving(): void
    {
        // Create a simple class that accepts parameters
        $result = app(\stdClass::class, []);
        
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    // config() helper tests

    #[Test]
    public function config_returns_config_instance_when_no_argument(): void
    {
        $result = config();
        
        $this->assertInstanceOf(ConfigInterface::class, $result);
    }

    #[Test]
    public function config_gets_value_by_key(): void
    {
        config()->set('test.key', 'test_value');
        
        $result = config('test.key');
        
        $this->assertSame('test_value', $result);
    }

    #[Test]
    public function config_returns_default_for_missing_key(): void
    {
        $result = config('non.existent.key', 'default');
        
        $this->assertSame('default', $result);
    }

    #[Test]
    public function config_sets_multiple_values_with_array(): void
    {
        $result = config(['batch.key1' => 'value1', 'batch.key2' => 'value2']);
        
        $this->assertNull($result);
        $this->assertSame('value1', config('batch.key1'));
        $this->assertSame('value2', config('batch.key2'));
    }

    // base_path() helper tests

    #[Test]
    public function base_path_returns_base_directory(): void
    {
        $result = base_path();
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function base_path_appends_path(): void
    {
        $result = base_path('app');
        
        $this->assertStringEndsWith('app', $result);
    }

    // logger() helper tests

    #[Test]
    public function logger_returns_logger_instance_when_no_message(): void
    {
        $result = logger();
        
        $this->assertInstanceOf(LoggerInterface::class, $result);
    }

    #[Test]
    public function logger_logs_message_when_provided(): void
    {
        // Should not throw
        $result = logger('Test message');
        
        $this->assertNull($result);
    }

    #[Test]
    public function logger_logs_message_with_context(): void
    {
        // Should not throw
        $result = logger('Test message', ['key' => 'value']);
        
        $this->assertNull($result);
    }

    // log_system() helper tests

    #[Test]
    public function log_system_logs_message(): void
    {
        // Should not throw
        log_system('System message');
        
        $this->assertTrue(true);
    }

    #[Test]
    public function log_system_logs_message_with_context(): void
    {
        // Should not throw
        log_system('System message', ['key' => 'value']);
        
        $this->assertTrue(true);
    }

    // db() helper tests

    #[Test]
    public function db_returns_database_manager(): void
    {
        $result = db();
        
        $this->assertInstanceOf(\App\Core\Database\DatabaseManager::class, $result);
    }

    // cache() helper tests

    #[Test]
    public function cache_returns_cache_manager_when_no_key(): void
    {
        $result = cache();
        
        $this->assertInstanceOf(\App\Core\Cache\CacheManager::class, $result);
    }

    #[Test]
    public function cache_returns_default_for_missing_key(): void
    {
        $result = cache('nonexistent_key', 'default_value');
        
        $this->assertSame('default_value', $result);
    }

    // e() helper tests

    #[Test]
    public function e_escapes_html(): void
    {
        $result = e('<script>alert("xss")</script>');
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    // rate_limit() helper tests

    #[Test]
    public function rate_limit_returns_true_when_allowed(): void
    {
        $key = 'test_rate_limit_' . uniqid();
        
        $result = rate_limit($key, 5, 60);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function rate_limit_allows_under_max(): void
    {
        $key = 'test_rate_limit_under_' . uniqid();
        
        // First check should pass
        $result1 = rate_limit($key, 2, 60);
        $this->assertTrue($result1);
        
        // Use same key multiple times
        rate_limit($key, 2, 60);
        $result2 = rate_limit($key, 2, 60);
        
        // Still allowed within limit
        $this->assertTrue($result2);
    }

    // rate_limit_hit() helper tests

    #[Test]
    public function rate_limit_hit_returns_count(): void
    {
        $key = 'test_rate_hit_' . uniqid();
        
        $count = rate_limit_hit($key, 60);
        
        $this->assertSame(1, $count);
    }

    #[Test]
    public function rate_limit_hit_increments(): void
    {
        $key = 'test_rate_hit_inc_' . uniqid();
        
        rate_limit_hit($key, 60);
        $count = rate_limit_hit($key, 60);
        
        $this->assertSame(2, $count);
    }

    #[Test]
    public function db_returns_manager_without_connection(): void
    {
        // db() without connection name should return the manager
        $manager = db();
        
        $this->assertInstanceOf(\App\Core\Database\DatabaseManager::class, $manager);
    }

    #[Test]
    public function log_system_works_with_logger(): void
    {
        // log_system should work without throwing
        log_system('Test system message', ['context' => 'test']);
        
        $this->assertTrue(true);
    }
}
