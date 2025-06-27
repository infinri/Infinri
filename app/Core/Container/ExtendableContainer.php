<?php declare(strict_types=1);

namespace App\Core\Container;

use DI\Container as BaseContainer;
use Psr\Container\ContainerInterface;

/** Extends DI container with service extension capability */
class ExtendableContainer extends BaseContainer
{
    /**
     * Extend a service with additional functionality
     * @template T
     * @param string $id Service identifier
     * @param callable(mixed, ContainerInterface): T $extender Callback that extends the service
     */
    public function extend(string $id, callable $extender): void
    {
        $original = $this->get($id);
        
        $this->set($id, function (ContainerInterface $c) use ($original, $extender) {
            return $extender($original, $c);
        });
    }
}
