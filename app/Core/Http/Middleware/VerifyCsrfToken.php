<?php

declare(strict_types=1);


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
use App\Core\Security\Csrf;

/**
 * Verify CSRF Token Middleware
 * 
 * Thin wrapper around Core\Security\Csrf for middleware pipeline.
 */
class VerifyCsrfToken
{
    protected Csrf $csrf;
    protected array $except;

    public function __construct(?Csrf $csrf = null, array $except = [])
    {
        $this->csrf = $csrf ?? new Csrf();
        $this->except = $except;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->shouldVerify($request) && !$this->tokensMatch($request)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'CSRF token mismatch',
                'message' => 'The page has expired. Please refresh and try again.',
            ]);
            exit;
        }

        $response = $next($request);

        // Add XSRF-TOKEN cookie for JS frameworks
        setcookie('XSRF-TOKEN', $this->csrf->token(), [
            'expires' => 0,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Strict',
            'secure' => isset($_SERVER['HTTPS']),
        ]);

        return $response;
    }

    protected function shouldVerify(RequestInterface $request): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        $path = $request->path();
        foreach ($this->except as $pattern) {
            if ($path === $pattern || fnmatch($pattern, $path)) {
                return false;
            }
        }

        return true;
    }

    protected function tokensMatch(RequestInterface $request): bool
    {
        $token = $request->input('csrf_token') 
            ?? $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN');

        return $token && $this->csrf->verify($token);
    }
}
