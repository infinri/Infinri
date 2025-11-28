<?php

declare(strict_types=1);

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
}
