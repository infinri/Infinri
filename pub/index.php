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

// Generate CSP nonce for inline styles (before any output)
$cspNonce = base64_encode(random_bytes(16));

// Build CSP header with nonce
$cspHeader = "Content-Security-Policy: default-src 'self'; img-src 'self' data:; " .
    "style-src 'self' 'nonce-" . $cspNonce . "'; script-src 'self'; " .
    "base-uri 'self'; frame-ancestors 'none'; form-action 'self'";

// Security Headers (must be before any output)
header($cspHeader);
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// HSTS only in production with HTTPS
if (Env::get('HTTPS_ONLY', false, 'bool') && Env::get('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Enable gzip compression for faster page loads
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

// Initialize session with security settings
$sessionPath = dirname(__DIR__) . '/var/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0700, true);
}
session_save_path($sessionPath);

// Configure session from .env
$sessionLifetime = (int)Env::get('SESSION_LIFETIME', '7200');
$sessionDomain = Env::get('SESSION_DOMAIN', '');

ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => $sessionDomain,
    'secure' => Env::get('HTTPS_ONLY', 'false') === 'true',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Note: session_start() will be called by Session::csrf() below
// We configure the params first, then let Session helper start it

// Store nonce for use in templates
View::set('cspNonce', $cspNonce);

// Load configuration
$config = require __DIR__ . '/../app/config.php';

// Pass config and CSRF token to views
// Session::csrf() will call Session::start() which calls session_start()
View::set('config', $config);
View::set('csrf', Session::csrf());

// Register base and frontend assets (always loaded)
// Critical hero CSS must be first for instant LCP
Assets::addCss('/assets/base/css/critical-hero.css', 'base');
Assets::addCss('/assets/base/css/reset.css', 'base');
Assets::addCss('/assets/base/css/variables.css', 'base');
Assets::addCss('/assets/base/css/base.css', 'base');
Assets::addCss('/assets/frontend/css/theme.css', 'frontend');

Assets::addJs('/assets/base/js/base.js', 'base');
Assets::addJs('/assets/frontend/js/theme.js', 'frontend');

// Define routes
$router = new Router();

$router->get('/', 'home')
       ->get('/about', 'about')
       ->get('/services', 'services')
       ->get('/contact', 'contact')
       ->post('/contact', function() {
           require __DIR__ . '/../app/modules/contact/api.php';
       });

// Dispatch
$router->dispatch('error');
