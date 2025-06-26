<?php declare(strict_types=1);

namespace App\Modules\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Module Event Dispatcher that implements PSR-14 EventDispatcherInterface
 */
class ModuleEventDispatcher implements EventDispatcherInterface
{
    private ListenerProviderInterface $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * Dispatches an event to all registered listeners
     *
     * @template T of object
     * @param T $event The event to dispatch
     * @return T The dispatched event
     */
    public function dispatch(object $event): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            // Call the listener with the event
            $listener($event);

            // Stop propagation if the event is marked as stopped
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
