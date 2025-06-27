<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\ModuleManager;
use App\Modules\ModuleInterface;
use App\Modules\ModuleMetadata;
use App\Modules\Validation\DependencyValidator;
use App\Modules\Validation\VersionValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class ModuleManagerTest extends TestCase
{
    private ModuleManager $manager;
    private MockObject $container;
    private MockObject $dispatcher;
    private string $modulesPath;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->modulesPath = __DIR__ . '/../../../Fixtures/modules';
        
        $this->manager = new ModuleManager(
            $this->container,
            $this->modulesPath,
            false, // Disable cache for tests
            new DependencyValidator(),
            new VersionValidator(),
            $this->dispatcher
        );
    }

    public function testRegisterModuleWithValidModule(): void
    {
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module',
                'name' => 'Test Module',
                'version' => '1.0.0',
            ]));
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('Test\\Module')
            ->willReturn($module);
        
        $registered = $this->manager->registerModule('Test\\Module');
        
        $this->assertSame($module, $registered);
        $this->assertTrue($this->manager->hasModule('Test\\Module'));
    }
    
    public function testRegisterModuleWithInvalidClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module class NonExistentClass does not exist');
        
        $this->manager->registerModule('NonExistentClass');
    }
    
    public function testRegisterModuleWithNonModuleClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module stdClass must implement');
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('stdClass')
            ->willReturn(new \stdClass());
        
        $this->manager->registerModule('stdClass');
    }
    
    public function testRegisterModules(): void
    {
        $module1 = $this->createMock(ModuleInterface::class);
        $module1->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module1',
                'name' => 'Test Module 1',
                'version' => '1.0.0',
            ]));
            
        $module2 = $this->createMock(ModuleInterface::class);
        $module2->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module2',
                'name' => 'Test Module 2',
                'version' => '1.0.0',
                'dependencies' => ['test/module1' => '^1.0'],
            ]));
        
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($module1, $module2);
        
        $modules = $this->manager->registerModules(['Test\\Module1', 'Test\\Module2']);
        
        $this->assertCount(2, $modules);
        $this->assertSame($module1, $modules[0]);
        $this->assertSame($module2, $modules[1]);
    }
    
    public function testResolveDependencies(): void
    {
        // This test would require mocking the ModuleRegistry and DependencyResolver
        // which is complex and might be better suited for integration tests
        $this->markTestIncomplete('Test requires refactoring for better testability');
    }
    
    public function testGetModule(): void
    {
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module',
                'name' => 'Test Module',
                'version' => '1.0.0',
            ]));
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('Test\\Module')
            ->willReturn($module);
        
        $this->manager->registerModule('Test\\Module');
        $retrieved = $this->manager->getModule('Test\\Module');
        
        $this->assertSame($module, $retrieved);
    }
    
    public function testGetModuleThrowsWhenNotFound(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->manager->getModule('NonExistentModule');
    }
    
    public function testHasModule(): void
    {
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module',
                'name' => 'Test Module',
                'version' => '1.0.0',
            ]));
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('Test\\Module')
            ->willReturn($module);
        
        $this->assertFalse($this->manager->hasModule('Test\\Module'));
        
        $this->manager->registerModule('Test\\Module');
        
        $this->assertTrue($this->manager->hasModule('Test\\Module'));
        $this->assertTrue($this->manager->hasModule($module));
    }
    
    public function testGetModules(): void
    {
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module',
                'name' => 'Test Module',
                'version' => '1.0.0',
            ]));
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('Test\\Module')
            ->willReturn($module);
        
        $this->manager->registerModule('Test\\Module');
        $modules = $this->manager->getModules();
        
        $this->assertCount(1, $modules);
        $this->assertSame($module, reset($modules));
    }
    
    public function testGetByInterface(): void
    {
        $module = $this->createMock(ModuleInterface::class);
        $module->method('getMetadata')
            ->willReturn(new ModuleMetadata([
                'id' => 'test/module',
                'name' => 'Test Module',
                'version' => '1.0.0',
            ]));
        
        $this->container->expects($this->once())
            ->method('get')
            ->with('Test\\Module')
            ->willReturn($module);
        
        $this->manager->registerModule('Test\\Module');
        
        $modules = $this->manager->getByInterface(ModuleInterface::class);
        $this->assertCount(1, $modules);
        $this->assertSame($module, reset($modules));
        
        $empty = $this->manager->getByInterface('NonExistentInterface');
        $this->assertCount(0, $empty);
    }
}
