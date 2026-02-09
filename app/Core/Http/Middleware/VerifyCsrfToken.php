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

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\HttpStatus;
use App\Core\Http\JsonResponse;
use App\Core\Security\Csrf;
use Closure;

/**
 * Verify CSRF Token Middleware
 *
 * Thin wrapper around Core\Security\Csrf for middleware pipeline.
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    protected Csrf $csrf;

    public function __construct(
        ?Csrf $csrf = null,
        protected array $except = []
    ) {
        $this->csrf = $csrf ?? app(Csrf::class);
    }

    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        if ($this->shouldVerify($request) && ! $this->tokensMatch($request)) {
            return new JsonResponse([
                'error' => 'CSRF token mismatch',
                'message' => 'The page has expired. Please refresh and try again.',
            ], HttpStatus::PAGE_EXPIRED);
        }

        $response = $next($request);

        // Add XSRF-TOKEN cookie for JS frameworks
        return $response->cookie(
            'XSRF-TOKEN',
            $this->csrf->token(),
            0,
            '/',
            '',
            $request->secure(),
            false,
            'Strict'
        );
    }

    protected function shouldVerify(RequestInterface $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
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
