<?php
declare(strict_types=1);
/**
 * Front Controller
 *
 * Application entry point — routes all requests through the Kernel
 * middleware pipeline for consistent security headers, session handling,
 * CSRF verification, and request lifecycle management.
 *
 * @package App
 */

// Load Composer autoloader and Core bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Contracts\Routing\RouterInterface;
use App\Core\Http\Kernel;
use App\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Core\Http\Middleware\StartSession;
use App\Core\Http\Middleware\VerifyCsrfToken;
use App\Core\Http\Request;
use App\Core\Http\WebExceptionHandler;
use App\Core\Module\ModuleRenderer;
use App\Core\Routing\Router;

// ─── Environment ───────────────────────────────────────────────

$isProduction = env('APP_ENV', 'production') === 'production';
$secureCookie = $isProduction || (bool) env('HTTPS_ONLY', false);

// ─── CSP Nonce ─────────────────────────────────────────────────

$cspNonce = SecurityHeadersMiddleware::generateNonce();
$app->instance('csp.nonce', $cspNonce);

// ─── Router + Routes ───────────────────────────────────────────

$router = new Router($app);
$app->instance(RouterInterface::class, $router);

// Load route definitions
require __DIR__ . '/../routes/web.php';

// ─── Kernel with Middleware ────────────────────────────────────

$kernel = new Kernel($app, $router);

// Use WebExceptionHandler for styled error pages via ModuleRenderer
$exceptionHandler = new WebExceptionHandler(config('app.debug', false));
$exceptionHandler->setRenderer(new ModuleRenderer());
$kernel->setExceptionHandler($exceptionHandler);

$kernel->setMiddleware([
    SecurityHeadersMiddleware::class,
    StartSession::class,
    VerifyCsrfToken::class,
]);

// Configure middleware instances in the container
$app->instance(SecurityHeadersMiddleware::class, new SecurityHeadersMiddleware([
    'hsts' => $secureCookie && $isProduction,
    'csp_nonce' => $cspNonce,
    'headers' => [
        'Content-Security-Policy' => SecurityHeadersMiddleware::buildCsp([
            'script-src' => "'self' 'nonce-{$cspNonce}' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/",
            'style-src' => "'self' 'nonce-{$cspNonce}'",
            'frame-src' => "https://www.google.com/recaptcha/ https://recaptcha.google.com/",
            'connect-src' => "'self' https://www.google.com/recaptcha/",
        ], $cspNonce),
    ],
]));

$app->instance(StartSession::class, new StartSession(
    lifetime: (int) env('SESSION_LIFETIME', 7200),
    domain: env('SESSION_DOMAIN', ''),
    secure: $secureCookie,
    savePath: dirname(__DIR__) . '/var/sessions'
));

$app->instance(VerifyCsrfToken::class, new VerifyCsrfToken(
    except: ['/_metrics']
));

// ─── Load Config ───────────────────────────────────────────────

$configArray = require __DIR__ . '/../app/config.php';
$app->instance('config.array', $configArray);

// ─── Assets (development only) ─────────────────────────────────

$assets = app(\App\Core\View\Asset\AssetManager::class);
$assets->setNonce($cspNonce);

if (!$isProduction) {
    // Core CSS
    $assets->addCss('/assets/core/base/css/_reset.css');
    $assets->addCss('/assets/core/base/css/_variables.css');
    $assets->addCss('/assets/core/base/css/components.css');
    $assets->addCss('/assets/core/frontend/css/layout.css');

    // Theme CSS (from Modules/Theme)
    $assets->addCss('/assets/theme/frontend/css/theme.css');

    // Core JS
    $assets->addJs('/assets/core/base/js/core.js');

    // Theme JS (from Modules/Theme)
    $assets->addJs('/assets/theme/frontend/js/theme.js');
}

// ─── Handle Request ────────────────────────────────────────────

$request = Request::capture();
$app->instance(Request::class, $request);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
