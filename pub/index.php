<?php
declare(strict_types=1);
/**
 * Front Controller
 *
 * Application entry point with security headers and routing
 *
 * @package App
 */

// Load Composer autoloader and Core bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Routing\SimpleRouter;
use App\Core\Http\Request;

// Generate CSP nonce for inline styles and scripts (before any output)
$cspNonce = base64_encode(random_bytes(16));

// Store nonce in container for access via csp_nonce() helper
$app->instance('csp.nonce', $cspNonce);

// Build CSP header with nonce
$cspHeader = "Content-Security-Policy: default-src 'self'; img-src 'self' data:; " .
    "style-src 'self' 'nonce-{$cspNonce}'; " .
    "script-src 'self' 'nonce-{$cspNonce}' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
    "frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/; " .
    "connect-src 'self' https://www.google.com/recaptcha/; " .
    "base-uri 'self'; frame-ancestors 'none'; form-action 'self'";

// Security Headers (must be before any output)
header($cspHeader);
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// HSTS only in production with HTTPS
if (env('HTTPS_ONLY', false) && env('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Enable gzip compression for faster page loads
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

// Configure session
$sessionPath = dirname(__DIR__) . '/var/sessions';
$sessionLifetime = (int) env('SESSION_LIFETIME', 7200);
$sessionDomain = env('SESSION_DOMAIN', '');
$isProduction = env('APP_ENV', 'production') === 'production';
$secureCookie = $isProduction || (bool) env('HTTPS_ONLY', false);

session_save_path($sessionPath);
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => $sessionDomain,
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Handle internal metrics endpoint (before session/CSRF overhead)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if ($requestUri === '/_metrics' || str_starts_with($requestUri, '/_metrics?')) {
    $metricsEndpoint = new \App\Core\Http\MetricsEndpoint();
    if ($metricsEndpoint->authorize()) {
        $metricsEndpoint->handle();
        exit;
    }
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Capture request and store in container
$request = Request::capture();
$app->instance(Request::class, $request);

// Load configuration into container
$configArray = require __DIR__ . '/../app/config.php';
$app->instance('config.array', $configArray);

// Generate CSRF token (will call session_start())
csrf_token();

// Initialize AssetManager with CSP nonce
$assets = app(\App\Core\View\Asset\AssetManager::class);
$assets->setNonce($cspNonce);

// Register assets (development only - production uses minified bundles)
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

// Define routes
$router = new SimpleRouter();

$router->get('/', 'home')
       ->get('/about', 'about')
       ->get('/services', 'services')
       ->get('/contact', 'contact')
       ->post('/contact', 'contact')
       ->get('/terms', 'legal')
       ->get('/privacy', 'legal')
       ->get('/cookies', 'legal')
       ->get('/disclaimer', 'legal')
       ->get('/refund', 'legal');

// Dispatch
$router->dispatch('error');
