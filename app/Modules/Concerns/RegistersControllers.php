<?php declare(strict_types=1);

namespace App\Modules\Concerns;

use Psr\Container\ContainerInterface;

/**
 * Trait for modules that need to register controllers
 */
trait RegistersControllers
{
    /**
     * Register a controller with the container
     * 
     * @param string $controllerClass The controller class to register
     * @param array $dependencies Array of service IDs to inject into the controller
     */
    protected function registerController(string $controllerClass, array $dependencies = []): void
    {
        $this->container->set($controllerClass, function(ContainerInterface $c) use ($controllerClass, $dependencies) {
            $args = [];
            foreach ($dependencies as $dependency) {
                $args[] = $c->get($dependency);
            }
            
            return new $controllerClass(...$args);
        });
    }
    
    /**
     * Register multiple controllers at once
     * 
     * @param array $controllers Array of controller classes and their dependencies
     * @example [
     *     ControllerA::class => [ServiceA::class, 'view'],
     *     ControllerB::class => [ServiceB::class, LoggerInterface::class]
     * ]
     */
    protected function registerControllers(array $controllers): void
    {
        foreach ($controllers as $controller => $dependencies) {
            $this->registerController($controller, (array)$dependencies);
        }
    }
}
