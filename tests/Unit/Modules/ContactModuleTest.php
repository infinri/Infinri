<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Contact\ContactModule;
use App\Modules\Contact\Controllers\ContactController;
use App\Modules\Core\Services\NotificationService;
use League\Plates\Engine;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tests\Unit\Modules\ModuleTestCase;

class ContactModuleTest extends ModuleTestCase
{
    private ContactModule $module;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up required container entries
        $this->container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));
        $this->container->set(Engine::class, $this->createMock(Engine::class));
        $this->container->set('view.paths', []);
        
        $this->module = new ContactModule($this->container);
    }
    
    #[Test]
    public function it_registers_contact_services(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $this->assertTrue($this->container->has(ContactController::class));
        $this->assertTrue($this->container->has(NotificationService::class));
    }
    
    #[Test]
    public function it_registers_view_paths(): void
    {
        // Arrange
        $initialPaths = $this->container->get('view.paths');
        
        // Act
        $this->module->register();
        
        // Assert
        $paths = $this->container->get('view.paths');
        $this->assertCount(count($initialPaths) + 1, $paths);
        $this->assertStringContainsString('Contact/Views', end($paths));
    }
    
    #[Test]
    public function it_registers_contact_controller(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $controller = $this->container->get(ContactController::class);
        $this->assertInstanceOf(ContactController::class, $controller);
    }
    
    #[Test]
    public function it_boots_without_errors(): void
    {
        // This is mostly to ensure the boot method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        
        $this->module->boot();
    }
}
