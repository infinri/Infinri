<?php declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $group) {
    $group->get('/', 'App\\Modules\\Core\\Controllers\\HomeController:index')->setName('home');
};
