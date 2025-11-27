<?php

declare(strict_types=1);

namespace App\Core\Events;

use App\Core\Contracts\Events\EventDispatcherInterface;

/**
 * Event Dispatcher
 * 
 * Dispatches events to registered listeners with priority support.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Registered listeners grouped by event
     * @var array<string, array<int, array<callable>>>
     */
    protected array $listeners = [];

    /**
     * Sorted listeners cache
     * @var array<string, callable[]>
     */
    protected array $sorted = [];

    /**
     * Dispatch an event to all registered listeners
     */
    public function dispatch(object $event): object
    {
        $eventName = get_class($event);

        foreach ($this->getListeners($eventName) as $listener) {
            if ($event instanceof StoppableEvent && $event->isPropagationStopped()) {
                break;
            }

            $this->callListener($listener, $event);
        }

        return $event;
    }

    /**
     * Register an event listener
     */
    public function listen(string $event, callable|string $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
        unset($this->sorted[$event]);
    }

    /**
     * Register a subscriber (class with multiple listeners)
     */
    public function subscribe(string|object $subscriber): void
    {
        $subscriber = is_string($subscriber) ? new $subscriber() : $subscriber;

        if (!method_exists($subscriber, 'subscribe')) {
            throw new \InvalidArgumentException(
                'Subscriber must implement a subscribe() method'
            );
        }

        $subscriber->subscribe($this);
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    /**
     * Get all listeners for an event, sorted by priority
     */
    public function getListeners(string $event): array
    {
        if (!isset($this->listeners[$event])) {
            return [];
        }

        if (!isset($this->sorted[$event])) {
            $this->sorted[$event] = $this->sortListeners($event);
        }

        return $this->sorted[$event];
    }

    /**
     * Remove a listener from an event
     */
    public function forget(string $event, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset($this->listeners[$event], $this->sorted[$event]);
            return;
        }

        foreach ($this->listeners[$event] ?? [] as $priority => $listeners) {
            foreach ($listeners as $index => $registered) {
                if ($registered === $listener) {
                    unset($this->listeners[$event][$priority][$index]);
                }
            }
        }

        unset($this->sorted[$event]);
    }

    /**
     * Sort listeners by priority (higher = earlier)
     */
    protected function sortListeners(string $event): array
    {
        $listeners = $this->listeners[$event] ?? [];
        krsort($listeners); // Sort by priority descending

        $sorted = [];
        foreach ($listeners as $priority => $group) {
            foreach ($group as $listener) {
                $sorted[] = $listener;
            }
        }

        return $sorted;
    }

    /**
     * Call a listener with the event
     */
    protected function callListener(callable|string $listener, object $event): void
    {
        if (is_string($listener)) {
            // Class@method syntax
            if (str_contains($listener, '@')) {
                [$class, $method] = explode('@', $listener);
                $listener = [new $class(), $method];
            } else {
                // Assume __invoke
                $listener = new $listener();
            }
        }

        $listener($event);
    }
}
