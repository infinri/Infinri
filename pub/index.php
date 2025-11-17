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
use App\Helpers\{Session, Env};
use App\Base\Helpers\Assets;

// Generate CSP nonce for inline styles (before any output)
$cspNonce = base64_encode(random_bytes(16));

// Build CSP header with nonce
$cspHeader = "Content-Security-Policy: default-src 'self'; img-src 'self' data:; " .
    "style-src 'self' 'nonce-" . $cspNonce . "'; " .
    "script-src 'self' 'nonce-" . $cspNonce . "' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
    "frame-src https://www.google.com/recaptcha/; " .
    "connect-src 'self' https://www.google.com/recaptcha/; " .
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

// Configure session settings (deferred initialization for performance)
$sessionPath = dirname(__DIR__) . '/var/sessions';
$sessionLifetime = (int)Env::get('SESSION_LIFETIME', '7200');
$sessionDomain = Env::get('SESSION_DOMAIN', '');

// Only create session directory if it doesn't exist (avoid unnecessary filesystem check)
if (!@is_dir($sessionPath)) {
    @mkdir($sessionPath, 0770, true);
    @chgrp($sessionPath, 'www-data');
}

session_save_path($sessionPath);
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100'); // 1% chance of GC

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => $sessionDomain,
    'secure' => Env::get('HTTPS_ONLY', 'false') === 'true',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Note: session_start() deferred - called by Session::csrf() only when needed

// Store nonce and config as globals for template access
$GLOBALS['cspNonce'] = $cspNonce;

// Load configuration
$config = require __DIR__ . '/../app/config.php';
$GLOBALS['config'] = $config;

// Generate CSRF token (will call session_start())
$csrfToken = Session::csrf();
$GLOBALS['csrf'] = $csrfToken;

// Debug logging removed - CSRF working correctly

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
       })
       ->get('/terms', 'legal')
       ->get('/privacy', 'legal')
       ->get('/cookies', 'legal')
       ->get('/disclaimer', 'legal')
       ->get('/refund', 'legal');

// Dispatch
$router->dispatch('error');
