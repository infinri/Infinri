<?php declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $group) {
    $group->get('/about', 'App\\Modules\\Pages\\Controllers\\PageController:about')->setName('about');
         
    $group->get('/services', 'App\\Modules\\Pages\\Controllers\\PageController:services')->setName('services');
};
