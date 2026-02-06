<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\JsonResponse;
use App\Modules\Auth\Services\AuthManager;
use Closure;

/**
 * Abstract Auth Middleware
 * 
 * Base class for authentication middleware providing common functionality.
 */
abstract class AbstractAuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected AuthManager $auth) {}

    abstract public function handle(RequestInterface $request, Closure $next): ResponseInterface;

    protected function expectsJson(RequestInterface $request): bool
    {
        $accept = $request->header('Accept', '');
        return str_contains($accept, 'application/json')
            || str_contains($accept, '*/*')
            || $this->isApiRequest($request);
    }

    protected function isApiRequest(RequestInterface $request): bool
    {
        return str_starts_with($request->getUri(), '/api/');
    }

    protected function jsonError(string $message, int $status = 403, ?string $code = null): JsonResponse
    {
        $data = ['success' => false, 'message' => $message];
        if ($code !== null) {
            $data['code'] = $code;
        }
        return new JsonResponse($data, $status);
    }
}
