<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

/**
 * Start Session Middleware
 *
 * Configures and starts the PHP session with secure defaults.
 * Replaces the manual session configuration previously in pub/index.php.
 */
class StartSession
{
    public function __construct(
        protected int $lifetime = 7200,
        protected string $domain = '',
        protected bool $secure = false,
        protected string $savePath = ''
    ) {
    }

    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        $this->configureSession();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $next($request);
    }

    protected function configureSession(): void
    {
        if ($this->savePath !== '') {
            session_save_path($this->savePath);
        }

        ini_set('session.gc_maxlifetime', (string) $this->lifetime);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        ini_set('session.cookie_secure', $this->secure ? '1' : '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        session_set_cookie_params([
            'lifetime' => $this->lifetime,
            'path' => '/',
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
