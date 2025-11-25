<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Application;
use App\Core\Container\Container;
use App\Core\Contracts\Config\ConfigInterface;
use App\Core\Contracts\Container\ContainerInterface;
use App\Core\Contracts\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        // Create test .env file if it doesn't exist
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_NAME=Infinri\nAPP_ENV=testing\nAPP_DEBUG=true\n");
        }
        
        $this->app = new Application(BASE_PATH);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    /** @test */
    public function it_extends_container(): void
    {
        $this->assertInstanceOf(Container::class, $this->app);
    }

    /** @test */
    public function it_implements_container_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->app);
    }

    /** @test */
    public function it_returns_correct_version(): void
    {
        $this->assertEquals('0.1.0', $this->app->version());
    }

    /** @test */
    public function it_registers_itself_as_singleton(): void
    {
        $resolved = $this->app->make(Application::class);
        
        $this->assertSame($this->app, $resolved);
    }

    /** @test */
    public function it_can_be_retrieved_via_static_method(): void
    {
        $instance = Application::getInstance();
        
        $this->assertSame($this->app, $instance);
    }

    /** @test */
    public function it_provides_base_path(): void
    {
        $basePath = $this->app->basePath();
        
        $this->assertEquals(BASE_PATH, $basePath);
    }

    /** @test */
    public function it_provides_app_path(): void
    {
        $appPath = $this->app->appPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'app', $appPath);
    }

    /** @test */
    public function it_provides_storage_path(): void
    {
        $storagePath = $this->app->storagePath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'var', $storagePath);
    }

    /** @test */
    public function it_provides_public_path(): void
    {
        $publicPath = $this->app->publicPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'pub', $publicPath);
    }

    /** @test */
    public function it_provides_config_path(): void
    {
        $configPath = $this->app->configPath();
        
        $this->assertEquals(BASE_PATH . DIRECTORY_SEPARATOR . 'config', $configPath);
    }

    /** @test */
    public function it_bootstraps_successfully(): void
    {
        $this->app->bootstrap();
        
        $this->assertTrue($this->app->bound(ConfigInterface::class));
        $this->assertTrue($this->app->bound(LoggerInterface::class));
    }

    /** @test */
    public function it_loads_configuration_on_bootstrap(): void
    {
        $this->app->bootstrap();
        
        $config = $this->app->make(ConfigInterface::class);
        
        $this->assertNotNull($config->get('app.name'));
        $this->assertNotNull($config->get('app.env'));
    }

    /** @test */
    public function it_creates_logger_on_bootstrap(): void
    {
        $this->app->bootstrap();
        
        $logger = $this->app->make(LoggerInterface::class);
        
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /** @test */
    public function it_detects_debug_mode(): void
    {
        $this->app->bootstrap();
        
        // Should be true based on .env
        $this->assertTrue($this->app->isDebug());
    }

    /** @test */
    public function it_detects_environment(): void
    {
        $this->app->bootstrap();
        
        $this->assertEquals('testing', $this->app->environment());
    }

    /** @test */
    public function it_detects_console_mode(): void
    {
        // PHPUnit runs in CLI mode
        $this->assertTrue($this->app->runningInConsole());
    }

    /** @test */
    public function it_does_not_bootstrap_twice(): void
    {
        $this->app->bootstrap();
        $first = $this->app->make(ConfigInterface::class);
        
        $this->app->bootstrap();
        $second = $this->app->make(ConfigInterface::class);
        
        $this->assertSame($first, $second);
    }
}
