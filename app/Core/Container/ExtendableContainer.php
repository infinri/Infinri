<?php declare(strict_types=1);

namespace App\Core\Container;

use DI\Container as BaseContainer;
use Psr\Container\ContainerInterface;

/**
 * Extends the base DI container with additional functionality
 */
class ExtendableContainer extends BaseContainer
{
    /**
     * Extend an existing service
     * 
     * @template T
     * @param string $id The service identifier
     * @param callable(mixed, ContainerInterface): T $extender Function to extend the service
     */
    public function extend(string $id, callable $extender): void
    {
        $original = $this->get($id);
        
        $this->set($id, function (ContainerInterface $c) use ($original, $extender, $id) {
            return $extender($original, $c);
        });
    }
}
