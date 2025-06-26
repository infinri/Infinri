<?php declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Modules\Events\ModuleEvent;
use App\Modules\Events\ModuleListenerProvider;
use App\Modules\ModuleInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class ModuleListenerProviderTest extends TestCase
{
    private ModuleListenerProvider $provider;
    private ModuleInterface|MockObject $module;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->module = $this->createMock(ModuleInterface::class);
        $this->provider = new ModuleListenerProvider();
    }

    public function testAddAndGetListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $called = false;
        
        $listener = function (ModuleEvent $e) use (&$called, $event) {
            $this->assertSame($event, $e);
            $called = true;
        };
        
        // Add listener and verify it's returned
        $this->provider->addListener(ModuleEvent::class, $listener);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        
        $this->assertCount(1, $listeners, 'Should return exactly one listener');
        $this->assertSame($listener, $listeners[0], 'Returned listener does not match added listener');
        
        // Call the listener and verify it works
        $listeners[0]($event);
        $this->assertTrue($called, 'Listener was not called correctly');
    }
    
    public function testGetListenersForEventWithNoListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        
        $this->assertEmpty($listeners, 'Should return no listeners when none are registered');
    }
    
    public function testGetListenersForEventWithMultipleListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $listener1 = function () {};
        $listener2 = function () {};
        
        $this->provider->addListener(ModuleEvent::class, $listener1);
        $this->provider->addListener(ModuleEvent::class, $listener2);
        
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        
        $this->assertCount(2, $listeners, 'Should return all registered listeners');
        $this->assertContains($listener1, $listeners, 'First listener not found');
        $this->assertContains($listener2, $listeners, 'Second listener not found');
    }
    
    public function testGetListenersForEventWithInterface(): void
    {
        $event = new class($this->module) extends ModuleEvent implements StoppableEventInterface {
            public function isPropagationStopped(): bool { return false; }
        };
        
        $called = false;
        $listener = function (StoppableEventInterface $e) use (&$called, $event) {
            $this->assertSame($event, $e);
            $called = true;
        };
        
        $this->provider->addListener(StoppableEventInterface::class, $listener);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        
        $this->assertCount(1, $listeners, 'Should return interface-based listener');
        
        $listeners[0]($event);
        $this->assertTrue($called, 'Interface-based listener was not called correctly');
    }
    
    public function testRemoveListeners(): void
    {
        $event = new ModuleEvent($this->module);
        $listener1 = function () {};
        $listener2 = function () {};
        
        // Add two listeners
        $this->provider->addListener(ModuleEvent::class, $listener1);
        $this->provider->addListener(ModuleEvent::class, $listener2);
        
        // Verify both are there
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertCount(2, $listeners, 'Should have two listeners before removal');
        
        // Remove all listeners for the event class
        $this->provider->removeListeners(ModuleEvent::class);
        
        // Verify no listeners remain
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertEmpty($listeners, 'Listeners were not removed');
    }
    
    public function testMultipleEventClasses(): void
    {
        $event1 = new ModuleEvent($this->module);
        $event2 = new class($this->module) extends ModuleEvent {
            // Different event class
        };
        
        $called1 = false;
        $called2 = false;
        
        $listener1 = function () use (&$called1) { $called1 = true; };
        $listener2 = function () use (&$called2) { $called2 = true; };
        
        // Add listeners for different event classes
        $this->provider->addListener(ModuleEvent::class, $listener1);
        $this->provider->addListener(get_class($event2), $listener2);
        
        // Verify each listener only responds to its event
        $listeners1 = iterator_to_array($this->provider->getListenersForEvent($event1));
        $listeners2 = iterator_to_array($this->provider->getListenersForEvent($event2));
        
        $this->assertCount(1, $listeners1, 'Should have one listener for event1');
        $this->assertCount(1, $listeners2, 'Should have one listener for event2');
        
        $listeners1[0]($event1);
        $listeners2[0]($event2);
        
        $this->assertTrue($called1, 'First listener was not called');
        $this->assertTrue($called2, 'Second listener was not called');
    }
}
