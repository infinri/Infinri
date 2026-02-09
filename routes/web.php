<?php declare(strict_types=1);

/**
 * Infinri Framework — Web Routes
 *
 * Registers front-end module routes on the full Router.
 * Each route renders a module via ModuleRenderer and returns a Response.
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

use App\Core\Contracts\Routing\RouterInterface;
use App\Core\Http\Response;
use App\Core\Module\ModuleRenderer;

/** @var RouterInterface $router */

$renderer = new ModuleRenderer();

/**
 * Helper: render a module and return an HTML Response
 */
$module = function (string $moduleName, int $status = 200) use ($renderer): \Closure {
    return function () use ($renderer, $moduleName, $status) {
        return Response::make($renderer->render($moduleName), $status)->asHtml();
    };
};

// ─── Page Routes ───────────────────────────────────────────────

$router->get('/', $module('home'));
$router->get('/about', $module('about'));
$router->get('/services', $module('services'));
$router->get('/contact', $module('contact'));
$router->post('/contact', $module('contact'));
$router->get('/terms', $module('legal'));
$router->get('/privacy', $module('legal'));
$router->get('/cookies', $module('legal'));
$router->get('/disclaimer', $module('legal'));
$router->get('/refund', $module('legal'));

// ─── Internal Endpoints ────────────────────────────────────────

$router->get('/_metrics', function () {
    $endpoint = new \App\Core\Http\MetricsEndpoint();
    if ($endpoint->authorize()) {
        ob_start();
        $endpoint->handle();
        $content = ob_get_clean();

        return Response::make($content)->header('Content-Type', 'application/json');
    }

    return Response::make('Forbidden', 403);
});
