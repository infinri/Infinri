<?php declare(strict_types=1);

namespace App\Modules\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Module Listener Provider that implements PSR-14 ListenerProviderInterface
 */
class ModuleListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, array<callable>>
     */
    private array $listeners = [];

    /**
     * Add a listener for a specific event class
     *
     * @template T of object
     * @param class-string<T> $eventClass
     * @param callable(T):void $listener
     */
    public function addListener(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Remove all listeners for a specific event class
     *
     * @param class-string $eventClass
     */
    public function removeListeners(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = \get_class($event);
        
        // Return listeners registered for the specific event class and its parents
        $classes = array_merge([$eventClass], class_parents($event));
        foreach ($classes as $class) {
            if (isset($this->listeners[$class])) {
                foreach ($this->listeners[$class] as $listener) {
                    yield $listener;
                }
            }
        }

        // Also return listeners for interfaces the event implements
        $interfaces = class_implements($event);
        foreach ($interfaces as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $listener) {
                    yield $listener;
                }
            }
        }
    }
}
