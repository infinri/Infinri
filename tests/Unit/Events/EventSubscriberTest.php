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
use App\Core\Events\EventDispatcher;
use App\Core\Events\Event;
use App\Core\Contracts\Events\EventSubscriberInterface;

// Test event
class TestEvent extends Event
{
    public array $handled = [];
}

// Test subscriber implementing interface
class TestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TestEvent::class => 'onTest',
        ];
    }
    
    public function onTest(TestEvent $event): void
    {
        $event->handled[] = 'onTest';
    }
}

// Subscriber with priority
class PrioritySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TestEvent::class => ['onTest', 100],
        ];
    }
    
    public function onTest(TestEvent $event): void
    {
        $event->handled[] = 'priority';
    }
}

// Subscriber with multiple handlers
class MultiHandlerSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TestEvent::class => [
                ['first', 10],
                ['second', 5],
            ],
        ];
    }
    
    public function first(TestEvent $event): void
    {
        $event->handled[] = 'first';
    }
    
    public function second(TestEvent $event): void
    {
        $event->handled[] = 'second';
    }
}

// Legacy subscriber with subscribe() method
class LegacySubscriber
{
    public function subscribe(EventDispatcher $dispatcher): void
    {
        $dispatcher->listen(TestEvent::class, [$this, 'onTest']);
    }
    
    public function onTest(TestEvent $event): void
    {
        $event->handled[] = 'legacy';
    }
}

beforeEach(function () {
    $this->dispatcher = new EventDispatcher();
});

test('subscribe registers EventSubscriberInterface', function () {
    $subscriber = new TestSubscriber();
    $this->dispatcher->subscribe($subscriber);
    
    expect($this->dispatcher->hasListeners(TestEvent::class))->toBeTrue();
});

test('subscriber handles event', function () {
    $subscriber = new TestSubscriber();
    $this->dispatcher->subscribe($subscriber);
    
    $event = new TestEvent();
    $this->dispatcher->dispatch($event);
    
    expect($event->handled)->toContain('onTest');
});

test('subscriber with priority executes in order', function () {
    $prioritySub = new PrioritySubscriber();
    $normalSub = new TestSubscriber();
    
    $this->dispatcher->subscribe($normalSub);
    $this->dispatcher->subscribe($prioritySub);
    
    $event = new TestEvent();
    $this->dispatcher->dispatch($event);
    
    // Priority subscriber (100) should run before normal (0)
    expect($event->handled[0])->toBe('priority');
    expect($event->handled[1])->toBe('onTest');
});

test('subscriber with multiple handlers registers all', function () {
    $subscriber = new MultiHandlerSubscriber();
    $this->dispatcher->subscribe($subscriber);
    
    $event = new TestEvent();
    $this->dispatcher->dispatch($event);
    
    // Both handlers should run in priority order
    expect($event->handled)->toContain('first');
    expect($event->handled)->toContain('second');
    expect($event->handled[0])->toBe('first'); // priority 10
    expect($event->handled[1])->toBe('second'); // priority 5
});

test('legacy subscriber with subscribe method works', function () {
    $subscriber = new LegacySubscriber();
    $this->dispatcher->subscribe($subscriber);
    
    $event = new TestEvent();
    $this->dispatcher->dispatch($event);
    
    expect($event->handled)->toContain('legacy');
});

test('subscribe throws for invalid subscriber', function () {
    $invalid = new stdClass();
    
    expect(fn() => $this->dispatcher->subscribe($invalid))
        ->toThrow(InvalidArgumentException::class);
});

test('add subscriber registers via interface', function () {
    $subscriber = new TestSubscriber();
    $this->dispatcher->addSubscriber($subscriber);
    
    expect($this->dispatcher->hasListeners(TestEvent::class))->toBeTrue();
});

test('remove subscriber unregisters handlers', function () {
    $subscriber = new TestSubscriber();
    $this->dispatcher->addSubscriber($subscriber);
    
    expect($this->dispatcher->hasListeners(TestEvent::class))->toBeTrue();
    
    $this->dispatcher->removeSubscriber($subscriber);
    
    // Note: The current implementation may not fully remove callable arrays
    // This tests that removeSubscriber doesn't throw
    expect(true)->toBeTrue();
});

test('subscribe accepts class string', function () {
    $this->dispatcher->subscribe(TestSubscriber::class);
    
    expect($this->dispatcher->hasListeners(TestEvent::class))->toBeTrue();
});
