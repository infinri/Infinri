<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Pages\Controllers\PageController;
use App\Modules\Pages\PagesModule;
use League\Plates\Engine;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Tests\Unit\Modules\ModuleTestCase;

class PagesModuleTest extends ModuleTestCase
{
    private PagesModule $module;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up required container entries
        $this->container->set(Engine::class, $this->createMock(Engine::class));
        $this->container->set('view.paths', []);
        
        $this->module = new PagesModule($this->container);
    }
    
    #[Test]
    public function it_registers_pages_services(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $this->assertTrue($this->container->has(PageController::class));
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
        $this->assertStringContainsString('Pages/Views', end($paths));
    }
    
    #[Test]
    public function it_registers_page_controller(): void
    {
        // Act
        $this->module->register();
        
        // Assert
        $controller = $this->container->get(PageController::class);
        $this->assertInstanceOf(PageController::class, $controller);
    }
    
    #[Test]
    public function it_boots_without_errors(): void
    {
        // This is mostly to ensure the boot method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        
        $this->module->boot();
    }
}
