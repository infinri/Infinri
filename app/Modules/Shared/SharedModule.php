<?php declare(strict_types=1);

namespace App\Modules\Shared;

use App\Modules\Module;
use Psr\Container\ContainerInterface;

class SharedModule extends Module
{
    public function register(): void
    {
        // Register shared services and middleware here
        $this->container->set('App\\Modules\\Shared\\Middleware\\RateLimitMiddleware', function($c) {
            return new Middleware\RateLimitMiddleware(
                $c->get('redis'), // Assuming Redis is available in the container
                $c->get('settings')['rate_limiting'] ?? []
            );
        });
    }

    public function boot(): void
    {
        // Boot shared module
    }
}
