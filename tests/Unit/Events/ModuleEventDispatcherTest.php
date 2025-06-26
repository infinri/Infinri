<?php declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Modules\Events\ModuleEvent;
use App\Modules\Events\ModuleEventDispatcher;
use App\Modules\Events\ModuleListenerProvider;
use App\Modules\ModuleInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class ModuleEventDispatcherTest extends TestCase
{
    private ModuleListenerProvider $listenerProvider;
    private ModuleEventDispatcher $dispatcher;
    private ModuleInterface|MockObject $module;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->module = $this->createMock(ModuleInterface::class);
        $this->listenerProvider = new ModuleListenerProvider();
        $this->dispatcher = new ModuleEventDispatcher($this->listenerProvider);
    }

    public function testDispatchCallsListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $called = false;
        
        $listener = function (ModuleEvent $e) use (&$called, $event) {
            $this->assertSame($event, $e);
            $called = true;
        };
        
        $this->listenerProvider->addListener(ModuleEvent::class, $listener);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($called, 'Listener was not called');
    }
    
    public function testDispatchWithMultipleListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $callCount = 0;
        
        $listener1 = function () use (&$callCount) { $callCount++; };
        $listener2 = function () use (&$callCount) { $callCount += 2; };
        
        $this->listenerProvider->addListener(ModuleEvent::class, $listener1);
        $this->listenerProvider->addListener(ModuleEvent::class, $listener2);
        
        $this->dispatcher->dispatch($event);
        
        $this->assertEquals(3, $callCount, 'Listeners were not called correctly');
    }
    
    public function testStopPropagation(): void
    {
        $event = new class($this->module) extends ModuleEvent {
            public function stopPropagation(): void {
                parent::stopPropagation();
            }
        };
        
        $callOrder = [];
        
        $listener1 = function () use (&$callOrder, $event) {
            $callOrder[] = 'listener1';
            $event->stopPropagation();
        };
        
        $listener2 = function () use (&$callOrder) {
            $callOrder[] = 'listener2';
        };
        
        $this->listenerProvider->addListener(ModuleEvent::class, $listener1);
        $this->listenerProvider->addListener(ModuleEvent::class, $listener2);
        
        $this->dispatcher->dispatch($event);
        
        $this->assertEquals(['listener1'], $callOrder, 'Propagation was not stopped');
        $this->assertTrue($event->isPropagationStopped(), 'Event should be marked as stopped');
    }
    
    public function testDispatchWithInterfaceListeners(): void
    {
        $event = new class($this->module) extends ModuleEvent implements StoppableEventInterface {
            public function isPropagationStopped(): bool { return false; }
        };
        
        $called = false;
        $listener = function (StoppableEventInterface $e) use (&$called, $event) {
            $this->assertSame($event, $e);
            $called = true;
        };
        
        $this->listenerProvider->addListener(StoppableEventInterface::class, $listener);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($called, 'Interface-based listener was not called');
    }
    
    public function testDispatchWithNoListeners(): void
    {
        $event = new ModuleEvent($this->module);
        
        // Should not throw any exceptions
        $this->dispatcher->dispatch($event);
        $this->assertTrue(true);
    }
}
