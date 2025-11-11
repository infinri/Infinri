<?php
declare(strict_types=1);
/**
 * Front Controller
 *
 * Application entry point with security headers and routing
 *
 * @package App
 */

// Load application bootstrap
require __DIR__ . '/../app/autoload.php';

use App\Core\Router;
use App\Helpers\{Session, View, Env};
use App\Base\Helpers\Assets;

// Security Headers
// phpcs:disable Generic.Strings.UnnecessaryStringConcat
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data:; " .
    "style-src 'self'; script-src 'self'; " .
    "base-uri 'self'; frame-ancestors 'none'; form-action 'self'"
);
// phpcs:enable Generic.Strings.UnnecessaryStringConcat
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// HSTS only in production with HTTPS
if (Env::get('HTTPS_ONLY', false, 'bool') && Env::get('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Start secure session
Session::start();

// Load configuration
$config = require __DIR__ . '/../app/config.php';

// Pass config and CSRF token to views
View::set('config', $config);
View::set('csrf', Session::csrf());

// Load base assets (shared across all pages)
Assets::addCss('/assets/base/css/reset.css', 'base');
Assets::addCss('/assets/base/css/variables.css', 'base');
Assets::addCss('/assets/base/css/base.css', 'base');
Assets::addJs('/assets/base/js/base.js', 'base');

// Load frontend theme assets
Assets::addCss('/assets/frontend/css/theme.css', 'frontend');
Assets::addJs('/assets/frontend/js/theme.js', 'frontend');

// Define routes
$router = new Router();

$router->get('/', 'home')
       ->get('/about', 'about')
       ->get('/services', 'services')
       ->get('/contact', 'contact');

// Dispatch
$router->dispatch('error');
