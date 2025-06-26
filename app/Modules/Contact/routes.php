<?php declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $group) {
    $group->get('/contact', 'App\\Modules\\Contact\\Controllers\\ContactController:showContactForm')->setName('contact.form');
         
    $group->post('/contact', 'App\\Modules\\Contact\\Controllers\\ContactController:handleContactForm')->setName('contact.submit');
};
