<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Core\CoreModule;
use App\Modules\Core\Services\NotificationService;
use League\Plates\Engine;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\Unit\Modules\ModuleTestCase;

class CoreModuleTest extends ModuleTestCase
{
    private CoreModule $module;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up required container entries
        $this->container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));
        $this->container->set('settings', [
            'app' => [
                'name' => 'Test App',
                'debug' => true,
            ],
            'view' => [
                'path' => __DIR__ . '/../../../resources/views',
                'cache' => false,
            ],
        ]);
        
        $this->module = new CoreModule($this->container);
    }
    
    #[Test]
    public function it_registers_core_services(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $this->assertTrue($this->container->has('view'));
        $this->assertTrue($this->container->has('router'));
        $this->assertTrue($this->container->has(NotificationService::class));
    }
    
    #[Test]
    public function it_registers_view_paths(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $view = $this->container->get('view');
        $this->assertInstanceOf(Engine::class, $view);
        
        // Check if the module's view path is registered
        $reflection = new \ReflectionProperty($view, 'directory');
        $reflection->setAccessible(true);
        $directory = $reflection->getValue($view);
        
        $this->assertStringContainsString('resources/views', (string) $directory);
    }
    
    #[Test]
    public function it_boots_without_errors(): void
    {
        // This is mostly to ensure the boot method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        
        $this->module->boot();
    }
}
