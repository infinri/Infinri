<?php declare(strict_types=1);

namespace App\Modules\Pages;

use App\Modules\Module;
use Psr\Container\ContainerInterface;

class PagesModule extends Module
{
    public function register(): void
    {
        // Register pages services
        $this->container->set('view.paths', function($c) {
            $paths = $c->has('view.paths') ? $c->get('view.paths') : [];
            $paths[] = $this->getViewsPath();
            return $paths;
        });

        // Register controllers
        $this->container->set('App\\Modules\\Pages\\Controllers\\PageController', function($c) {
            return new Controllers\PageController($c->get('view'));
        });
    }

    public function boot(): void
    {
        // Boot pages module
    }
}
