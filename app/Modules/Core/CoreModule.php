<?php declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Module;
use Psr\Container\ContainerInterface;

class CoreModule extends Module
{
    public function register(): void
    {
        // Register core services
        $this->container->set('view.paths', [
            $this->getViewsPath()
        ]);

        // Register controllers
        $this->container->set('App\\Modules\\Core\\Controllers\\HomeController', function($c) {
            return new Controllers\HomeController($c->get('view'));
        });
    }

    public function boot(): void
    {
        // Boot core module
    }
}
