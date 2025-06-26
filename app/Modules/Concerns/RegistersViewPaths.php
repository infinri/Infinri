<?php declare(strict_types=1);

namespace App\Modules\Concerns;

use Psr\Container\ContainerInterface;

/**
 * Trait for modules that need to register view paths
 */
trait RegistersViewPaths
{
    /**
     * Register view paths for the module
     */
    protected function registerViewPaths(): void
    {
        $this->container->extend('view.paths', function (array $paths, ContainerInterface $c) {
            $paths[] = $this->getViewsPath();
            return $paths;
        });
    }
}
