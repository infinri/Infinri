<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Events;

use App\Core\Contracts\Events\EventDispatcherInterface;
use App\Core\Contracts\Events\EventSubscriberInterface;
use InvalidArgumentException;

/**
 * Event Dispatcher
 *
 * Dispatches events to registered listeners with priority support.
 * Supports both legacy subscribe() method and EventSubscriberInterface.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Registered listeners grouped by event
     *
     * @var array<string, array<int, array<callable>>>
     */
    protected array $listeners = [];

    /**
     * Sorted listeners cache
     *
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
     *
     * Supports both:
     * - EventSubscriberInterface::getSubscribedEvents()
     * - Legacy subscribe($dispatcher) method
     */
    public function subscribe(string|object $subscriber): void
    {
        $subscriber = is_string($subscriber) ? new $subscriber() : $subscriber;

        // New interface-based subscriber
        if ($subscriber instanceof EventSubscriberInterface) {
            $this->addSubscriber($subscriber);

            return;
        }

        // Legacy subscribe() method
        if (method_exists($subscriber, 'subscribe')) {
            $subscriber->subscribe($this);

            return;
        }

        throw new InvalidArgumentException(
            'Subscriber must implement EventSubscriberInterface or have a subscribe() method'
        );
    }

    /**
     * Add an EventSubscriberInterface subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                // Simple: 'eventName' => 'methodName'
                $this->listen($eventName, [$subscriber, $params]);
            } elseif (is_array($params)) {
                if (is_string($params[0] ?? null)) {
                    // With priority: 'eventName' => ['methodName', priority]
                    $this->listen($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
                } else {
                    // Multiple handlers: 'eventName' => [['method1', priority], ['method2', priority]]
                    foreach ($params as $handler) {
                        $this->listen(
                            $eventName,
                            [$subscriber, $handler[0]],
                            $handler[1] ?? 0
                        );
                    }
                }
            }
        }
    }

    /**
     * Remove a subscriber
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->forget($eventName, [$subscriber, $params]);
            } elseif (is_array($params)) {
                if (is_string($params[0] ?? null)) {
                    $this->forget($eventName, [$subscriber, $params[0]]);
                } else {
                    foreach ($params as $handler) {
                        $this->forget($eventName, [$subscriber, $handler[0]]);
                    }
                }
            }
        }
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && $this->listeners[$event] !== [];
    }

    /**
     * Get all listeners for an event, sorted by priority
     */
    public function getListeners(string $event): array
    {
        if (! isset($this->listeners[$event])) {
            return [];
        }

        if (! isset($this->sorted[$event])) {
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
