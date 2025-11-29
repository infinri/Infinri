<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Events;

use App\Core\Events\EventDispatcher;
use App\Core\Events\Event;
use App\Core\Events\StoppableEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    #[Test]
    public function listen_registers_listener(): void
    {
        $this->dispatcher->listen(TestEvent::class, fn() => null);
        
        $this->assertTrue($this->dispatcher->hasListeners(TestEvent::class));
    }

    #[Test]
    public function has_listeners_returns_false_when_none(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('NonExistent'));
    }

    #[Test]
    public function dispatch_calls_listener(): void
    {
        $called = false;
        $this->dispatcher->listen(TestEvent::class, function($event) use (&$called) {
            $called = true;
        });
        
        $this->dispatcher->dispatch(new TestEvent());
        
        $this->assertTrue($called);
    }

    #[Test]
    public function dispatch_passes_event_to_listener(): void
    {
        $receivedEvent = null;
        $this->dispatcher->listen(TestEvent::class, function($event) use (&$receivedEvent) {
            $receivedEvent = $event;
        });
        
        $event = new TestEvent();
        $this->dispatcher->dispatch($event);
        
        $this->assertSame($event, $receivedEvent);
    }

    #[Test]
    public function dispatch_returns_event(): void
    {
        $event = new TestEvent();
        $result = $this->dispatcher->dispatch($event);
        
        $this->assertSame($event, $result);
    }

    #[Test]
    public function dispatch_calls_multiple_listeners(): void
    {
        $calls = [];
        $this->dispatcher->listen(TestEvent::class, function() use (&$calls) {
            $calls[] = 1;
        });
        $this->dispatcher->listen(TestEvent::class, function() use (&$calls) {
            $calls[] = 2;
        });
        
        $this->dispatcher->dispatch(new TestEvent());
        
        $this->assertCount(2, $calls);
    }

    #[Test]
    public function listen_respects_priority(): void
    {
        $calls = [];
        $this->dispatcher->listen(TestEvent::class, function() use (&$calls) {
            $calls[] = 'low';
        }, 0);
        $this->dispatcher->listen(TestEvent::class, function() use (&$calls) {
            $calls[] = 'high';
        }, 10);
        
        $this->dispatcher->dispatch(new TestEvent());
        
        $this->assertSame(['high', 'low'], $calls);
    }

    #[Test]
    public function get_listeners_returns_sorted_listeners(): void
    {
        $this->dispatcher->listen(TestEvent::class, fn() => 'a', 0);
        $this->dispatcher->listen(TestEvent::class, fn() => 'b', 10);
        
        $listeners = $this->dispatcher->getListeners(TestEvent::class);
        
        $this->assertCount(2, $listeners);
    }

    #[Test]
    public function get_listeners_returns_empty_for_unknown_event(): void
    {
        $listeners = $this->dispatcher->getListeners('Unknown');
        
        $this->assertSame([], $listeners);
    }

    #[Test]
    public function forget_removes_all_listeners_for_event(): void
    {
        $this->dispatcher->listen(TestEvent::class, fn() => null);
        $this->dispatcher->listen(TestEvent::class, fn() => null);
        
        $this->dispatcher->forget(TestEvent::class);
        
        $this->assertFalse($this->dispatcher->hasListeners(TestEvent::class));
    }

    #[Test]
    public function forget_removes_specific_listener(): void
    {
        $listener1 = fn() => 'a';
        $listener2 = fn() => 'b';
        
        $this->dispatcher->listen(TestEvent::class, $listener1);
        $this->dispatcher->listen(TestEvent::class, $listener2);
        
        $this->dispatcher->forget(TestEvent::class, $listener1);
        
        $this->assertTrue($this->dispatcher->hasListeners(TestEvent::class));
    }

    #[Test]
    public function stoppable_event_stops_propagation(): void
    {
        $calls = [];
        $this->dispatcher->listen(TestStoppableEvent::class, function($event) use (&$calls) {
            $calls[] = 1;
            $event->stopPropagation();
        });
        $this->dispatcher->listen(TestStoppableEvent::class, function() use (&$calls) {
            $calls[] = 2;
        });
        
        $this->dispatcher->dispatch(new TestStoppableEvent());
        
        $this->assertSame([1], $calls);
    }

    #[Test]
    public function subscribe_registers_subscriber(): void
    {
        $this->dispatcher->subscribe(new TestSubscriber());
        
        $this->assertTrue($this->dispatcher->hasListeners(TestEvent::class));
    }

    #[Test]
    public function subscribe_throws_for_invalid_subscriber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->dispatcher->subscribe(new \stdClass());
    }

    #[Test]
    public function subscribe_accepts_class_string(): void
    {
        $this->dispatcher->subscribe(TestSubscriber::class);
        
        $this->assertTrue($this->dispatcher->hasListeners(TestEvent::class));
    }

    #[Test]
    public function dispatch_with_class_at_method_listener(): void
    {
        $this->dispatcher->listen(TestEvent::class, TestMethodListener::class . '@handle');
        
        TestMethodListener::$called = false;
        $this->dispatcher->dispatch(new TestEvent());
        
        $this->assertTrue(TestMethodListener::$called);
    }

    #[Test]
    public function dispatch_with_invokable_class_listener(): void
    {
        $this->dispatcher->listen(TestEvent::class, TestInvokableListener::class);
        
        TestInvokableListener::$called = false;
        $this->dispatcher->dispatch(new TestEvent());
        
        $this->assertTrue(TestInvokableListener::$called);
    }
}

class TestEvent extends Event {}

class TestStoppableEvent extends Event {}

class TestSubscriber
{
    public function subscribe(EventDispatcher $dispatcher): void
    {
        $dispatcher->listen(TestEvent::class, fn() => null);
    }
}

class TestMethodListener
{
    public static bool $called = false;
    
    public function handle(TestEvent $event): void
    {
        self::$called = true;
    }
}

class TestInvokableListener
{
    public static bool $called = false;
    
    public function __invoke(TestEvent $event): void
    {
        self::$called = true;
    }
}
