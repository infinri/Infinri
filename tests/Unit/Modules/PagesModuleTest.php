<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\Pages\Controllers\PageController;
use App\Modules\Pages\PagesModule;
use App\Modules\Pages\Services\PageService;
use League\Plates\Engine;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Tests\Unit\Modules\ModuleTestCase;

class PagesModuleTest extends ModuleTestCase
{
    private PagesModule $module;
    private array $services = [];
    private array $viewPaths = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock logger and engine
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $engine = $this->createMock(Engine::class);
        
        // Initialize services storage with required services
        $this->services = [
            Engine::class => $engine,
            'view.paths' => [],
            PageService::class => $this->createMock(PageService::class),
            ContainerInterface::class => $this->container,
            \Psr\Log\LoggerInterface::class => $logger,
        ];
        
        // Configure container to handle service registration and retrieval
        $this->container->method('has')
            ->willReturnCallback(fn($id) => array_key_exists($id, $this->services) || $id === PageController::class);
            
        $this->container->method('get')
            ->willReturnCallback(function ($id) {
                if ($id === 'view.paths') {
                    return $this->services['view.paths'];
                }
                if (isset($this->services[$id])) {
                    return $this->services[$id];
                }
                throw new \RuntimeException("Service not found: $id");
            });
            
        $this->container->method('set')
            ->willReturnCallback(function ($id, $service) {
                $this->services[$id] = $service;
                return $this->container;
            });
            
        // Handle extend calls, specifically for view.paths
        $this->container->method('extend')
            ->willReturnCallback(function ($id, callable $extend) {
                $service = $this->services[$id] ?? [];
                $extended = $extend($service, $this->container);
                $this->services[$id] = $extended;
                return $this->container;
            });
            
        // Create the module
        $this->module = new PagesModule($this->container);
    }
    
    #[Test]
    public function it_registers_pages_services(): void
    {
        // Act
        $this->module->register();
        
        // Assert - The container should have the PageController registered as a factory
        $this->assertArrayHasKey(PageController::class, $this->services, 
            'PageController should be registered in the services array');
            
        // The registered service should be a callable (factory function)
        $factory = $this->services[PageController::class];
        $this->assertIsCallable($factory, 'PageController should be registered as a factory');
    }
    
    #[Test]
    public function it_registers_view_paths(): void
    {
        // Act
        $this->module->register();
        
        // Assert - Check that view paths were updated
        $this->assertArrayHasKey('view.paths', $this->services);
        $paths = $this->services['view.paths'];
        $this->assertIsArray($paths);
        
        // The view path should be added to the array
        $this->assertNotEmpty($paths, 'View paths should not be empty after registration');
        
        // Get the expected views path from the module
        $moduleViewsPath = dirname(__DIR__, 3) . '/app/Modules/Pages/resources/views';
        
        // Verify the path is in the registered paths
        $normalizedPaths = array_map('strval', $paths);
        $this->assertContains($moduleViewsPath, $normalizedPaths, 'Module views path should be registered. Paths: ' . print_r($paths, true));
    }
    
    #[Test]
    public function it_registers_page_controller(): void
    {
        // Act
        $this->module->register();
        
        // Assert - The container should be able to provide the PageController
        $this->assertArrayHasKey(PageController::class, $this->services, 
            'PageController should be in the services array after registration');
        
        // The service should be a closure that creates the controller
        $controllerFactory = $this->services[PageController::class];
        $this->assertIsCallable($controllerFactory, 'Controller factory should be callable');
    }
    
    #[Test]
    public function it_boots_without_errors(): void
    {
        // This is mostly to ensure the boot method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        
        $this->module->boot();
    }
}
