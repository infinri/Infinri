<?php declare(strict_types=1);

namespace App\Modules\Contact;

use App\Modules\Module;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ContactModule extends Module
{
    public function register(): void
    {
        // Register contact services
        $this->container->set('view.paths', function($c) {
            $paths = $c->has('view.paths') ? $c->get('view.paths') : [];
            $paths[] = $this->getViewsPath();
            return $paths;
        });

        // Register controllers
        $this->container->set('App\\Modules\\Contact\\Controllers\\ContactController', function($c) {
            return new Controllers\ContactController(
                $c->get('view'),
                $c->get(LoggerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Boot contact module
    }
}
