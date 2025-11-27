<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Application;
use App\Core\Container\Container;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Container\ContainerInterface;
use App\Core\Contracts\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        // Reset Application singleton
        Application::resetInstance();
        
        // Create test .env file if it doesn't exist
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_NAME=Infinri\nAPP_ENV=testing\nAPP_DEBUG=true\n");
        }
        
        $this->app = new Application(BASE_PATH);
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    #[Test]
    public function it_extends_container(): void
    {
        $this->assertInstanceOf(Container::class, $this->app);
    }

    #[Test]
    public function it_implements_container_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->app);
    }

    #[Test]
    public function it_returns_correct_version(): void
    {
        $this->assertEquals('0.1.0', $this->app->version());
    }

    #[Test]
    public function it_registers_itself_as_singleton(): void
    {
        $resolved = $this->app->make(Application::class);
        
        $this->assertSame($this->app, $resolved);
    }

    #[Test]
    public function it_can_be_retrieved_via_static_method(): void
    {
        $instance = Application::getInstance();
        
        $this->assertSame($this->app, $instance);
    }

    #[Test]
    public function it_provides_base_path(): void
    {
        $basePath = $this->app->basePath();
        
        $this->assertEquals(BASE_PATH, $basePath);
    }

    #[Test]
    public function it_provides_app_path(): void
    {
        $appPath = $this->app->appPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'app', $appPath);
    }

    #[Test]
    public function it_provides_storage_path(): void
    {
        $storagePath = $this->app->storagePath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'var', $storagePath);
    }

    #[Test]
    public function it_provides_public_path(): void
    {
        $publicPath = $this->app->publicPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'pub', $publicPath);
    }

    #[Test]
    public function it_provides_config_path(): void
    {
        $configPath = $this->app->configPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'config', $configPath);
    }

    #[Test]
    public function it_bootstraps_successfully(): void
    {
        $this->app->bootstrap();
        
        $this->assertTrue($this->app->bound(ConfigInterface::class));
        $this->assertTrue($this->app->bound(LoggerInterface::class));
    }

    #[Test]
    public function it_loads_configuration_on_bootstrap(): void
    {
        $this->app->bootstrap();
        
        $config = $this->app->make(ConfigInterface::class);
        
        $this->assertNotNull($config->get('app.name'));
        $this->assertNotNull($config->get('app.env'));
    }

    #[Test]
    public function it_creates_logger_on_bootstrap(): void
    {
        $this->app->bootstrap();
        
        $logger = $this->app->make(LoggerInterface::class);
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    #[Test]
    public function it_detects_debug_mode(): void
    {
        $this->app->bootstrap();
        
        // Should be true based on .env
        $this->assertTrue($this->app->isDebug());
    }

    #[Test]
    public function it_detects_environment(): void
    {
        $this->app->bootstrap();
        
        $this->assertEquals('testing', $this->app->environment());
    }

    #[Test]
    public function it_detects_console_mode(): void
    {
        // PHPUnit runs in CLI mode
        $this->assertTrue($this->app->runningInConsole());
    }

    #[Test]
    public function it_does_not_bootstrap_twice(): void
    {
        $this->app->bootstrap();
        $first = $this->app->make(ConfigInterface::class);
        
        $this->app->bootstrap();
        $second = $this->app->make(ConfigInterface::class);
        
        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_throws_exception_when_instantiated_twice(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application instance already exists');
        
        new Application(BASE_PATH);
    }

    #[Test]
    public function it_throws_exception_when_get_instance_before_instantiation(): void
    {
        Application::resetInstance();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Application not instantiated');
        
        Application::getInstance();
    }

    #[Test]
    public function it_can_register_service_provider_by_class_name(): void
    {
        $this->app->bootstrap();
        
        $provider = $this->app->register(TestServiceProvider::class);
        
        $this->assertInstanceOf(TestServiceProvider::class, $provider);
    }

    #[Test]
    public function it_can_register_service_provider_instance(): void
    {
        $this->app->bootstrap();
        
        $providerInstance = new TestServiceProvider($this->app);
        $provider = $this->app->register($providerInstance);
        
        $this->assertSame($providerInstance, $provider);
    }

    #[Test]
    public function it_returns_existing_provider_if_already_registered(): void
    {
        $this->app->bootstrap();
        
        $first = $this->app->register(TestServiceProvider::class);
        $second = $this->app->register(TestServiceProvider::class);
        
        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_can_force_re_registration_of_provider(): void
    {
        $this->app->bootstrap();
        
        $first = $this->app->register(TestServiceProvider::class);
        $second = $this->app->register(TestServiceProvider::class, true);
        
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function it_can_get_registered_provider(): void
    {
        $this->app->bootstrap();
        
        $this->app->register(TestServiceProvider::class);
        
        $provider = $this->app->getProvider(TestServiceProvider::class);
        
        $this->assertInstanceOf(TestServiceProvider::class, $provider);
    }

    #[Test]
    public function it_returns_null_for_unregistered_provider(): void
    {
        $this->app->bootstrap();
        
        $provider = $this->app->getProvider(TestServiceProvider::class);
        
        $this->assertNull($provider);
    }

    #[Test]
    public function it_boots_provider_immediately_if_already_bootstrapped(): void
    {
        $this->app->bootstrap();
        
        $provider = $this->app->register(BootableTestProvider::class);
        
        $this->assertTrue($provider->booted);
    }

    #[Test]
    public function it_can_boot_all_registered_providers(): void
    {
        $this->app->bootstrap();
        
        $provider = new BootableTestProvider($this->app);
        $provider->register();
        $provider->booted = false; // Reset for test
        
        // Re-register to add to providers array
        $this->app->register($provider, true);
        $provider->booted = false; // Reset again
        
        $this->app->boot();
        
        $this->assertTrue($provider->booted);
    }

    #[Test]
    public function it_provides_paths_with_subdirectories(): void
    {
        $this->assertEquals(
            BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core',
            $this->app->appPath('Core')
        );
        
        $this->assertEquals(
            BASE_PATH . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs',
            $this->app->storagePath('logs')
        );
        
        $this->assertEquals(
            BASE_PATH . DIRECTORY_SEPARATOR . 'pub' . DIRECTORY_SEPARATOR . 'assets',
            $this->app->publicPath('assets')
        );
        
        $this->assertEquals(
            BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
            $this->app->configPath('app.php')
        );
    }
}

// Test fixtures
use App\Core\Container\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('test.service', function () {
            return new \stdClass();
        });
    }
}

class BootableTestProvider extends ServiceProvider
{
    public bool $booted = false;

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->booted = true;
    }
}
