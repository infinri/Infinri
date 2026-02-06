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
namespace App\Http\Middleware;

use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use Closure;

/**
 * Trim Strings Middleware
 * 
 * Trims whitespace from all string input values
 */
class TrimStrings implements MiddlewareInterface
{
    /**
     * The attributes that should not be trimmed
     */
    protected array $except = [
        'password',
        'password_confirmation',
    ];

    /**
     * Handle an incoming request
     */
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        // Trim query parameters
        $this->trimParameters($request->query);
        
        // Trim request body parameters
        $this->trimParameters($request->request);
        
        return $next($request);
    }

    /**
     * Trim parameters in a parameter bag
     */
    protected function trimParameters(object $bag): void
    {
        $all = $bag->all();
        $trimmed = $this->trimArray($all);
        $bag->replace($trimmed);
    }

    /**
     * Recursively trim array values
     */
    protected function trimArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->trimArray($value);
            } elseif (is_string($value) && !in_array($key, $this->except, true)) {
                $array[$key] = trim($value);
            }
        }
        
        return $array;
    }
}
