<?php declare(strict_types=1);

namespace App\Modules\Concerns;

use Psr\Container\ContainerInterface;

/**
 * Trait for modules that need to register view paths
 */
trait RegistersViewPaths
{
    protected function registerViewPaths(): void
    {
        if (method_exists($this->container, 'extend')) {
            // Preferred: use container extension if available
            $this->container->extend('view.paths', function (array $paths, ContainerInterface $c) {
                $paths[] = $this->getViewsPath();
                return $paths;
            });
        } else {
            // Fallback: manually get/update/set entry
            $paths = [];
            if ($this->container->has('view.paths')) {
                $paths = $this->container->get('view.paths');
            }
            if (!in_array($this->getViewsPath(), $paths, true)) {
                $paths[] = $this->getViewsPath();
            }
            // Attempt to use set if available, otherwise ignore
            if (method_exists($this->container, 'set')) {
                $this->container->set('view.paths', $paths);
            }
        }
    }
}
