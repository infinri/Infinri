<?php declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Modules\Events\ModuleBootEvent;
use App\Modules\Events\ModuleDependencyEvent;
use App\Modules\Events\ModuleDiscoveryEvent;
use App\Modules\Events\ModuleEvent;
use App\Modules\Events\ModuleRegistrationEvent;
use App\Modules\ModuleInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class ModuleEventTest extends TestCase
{
    private ModuleInterface|MockObject $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = $this->createMock(ModuleInterface::class);
    }

    public function testBaseEventProperties(): void
    {
        $event = new ModuleEvent($this->module, ['key' => 'value']);
        
        $this->assertSame($this->module, $event->getModule());
        $this->assertSame('value', $event->getArgument('key'));
        $this->assertNull($event->getArgument('nonexistent', null));
        $this->assertFalse($event->isPropagationStopped());
        $this->assertNull($event->getError());
    }
    
    public function testEventPropagation(): void
    {
        $event = new ModuleEvent($this->module);
        
        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        
        $this->assertTrue($event->isPropagationStopped());
    }
    
    public function testEventWithError(): void
    {
        $error = new \RuntimeException('Test error');
        $event = new ModuleEvent($this->module, [], $error);
        
        $this->assertSame($error, $event->getError());
    }
    
    public function testModuleBootEvent(): void
    {
        $event = new ModuleBootEvent($this->module, false);
        
        $this->assertSame($this->module, $event->getModule());
        $this->assertFalse($event->isSuccessful());
        $this->assertFalse($event->isPropagationStopped());
        
        // Test with success
        $event = new ModuleBootEvent($this->module, true);
        $this->assertTrue($event->isSuccessful());
    }
    
    public function testModuleDependencyEvent(): void
    {
        $dependencies = ['module1', 'module2'];
        $missing = ['missing' => '1.0'];
        $conflicts = ['conflict' => '1.0'];
        
        $event = new ModuleDependencyEvent(
            $this->module,
            $dependencies,
            $missing,
            $conflicts
        );
        
        $this->assertSame($dependencies, $event->getDependencies());
        $this->assertSame($missing, $event->getMissingDependencies());
        $this->assertSame($conflicts, $event->getVersionConflicts());
        $this->assertFalse($event->hasErrors());
        
        // Test with errors
        $event = new ModuleDependencyEvent(
            $this->module,
            [],
            $missing,
            $conflicts
        );
        
        $this->assertTrue($event->hasErrors());
    }
    
    public function testModuleDiscoveryEvent(): void
    {
        $modules = ['Module1', 'Module2'];
        $event = new ModuleDiscoveryEvent($modules);
        
        $this->assertSame($modules, $event->getDiscoveredModules());
        $this->assertFalse($event->isFromCache());
        
        // Test with cache flag
        $event = new ModuleDiscoveryEvent($modules, true);
        $this->assertTrue($event->isFromCache());
    }
    
    public function testModuleRegistrationEvent(): void
    {
        $event = new ModuleRegistrationEvent($this->module, false);
        
        $this->assertSame($this->module, $event->getModule());
        $this->assertFalse($event->isSuccessful());
        
        // Test with success
        $event = new ModuleRegistrationEvent($this->module, true);
        $this->assertTrue($event->isSuccessful());
    }
    
    public function testStoppableEventInterface(): void
    {
        $event = new class($this->module) extends ModuleEvent implements StoppableEventInterface {
            private bool $stopped = false;
            
            public function isPropagationStopped(): bool
            {
                return $this->stopped;
            }
            
            public function stopPropagation(): void
            {
                $this->stopped = true;
            }
        };
        
        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
}
