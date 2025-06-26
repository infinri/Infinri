<?php declare(strict_types=1);

namespace App\Modules\Events;

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventServiceProvider
{
    public function __invoke(Container $container): void
    {
        // Register the listener provider
        $container->set(ListenerProviderInterface::class, function (ContainerInterface $c) {
            return new ModuleListenerProvider();
        });

        // Register the event dispatcher
        $container->set(EventDispatcherInterface::class, function (ContainerInterface $c) {
            return new ModuleEventDispatcher(
                $c->get(ListenerProviderInterface::class)
            );
        });
    }
}
