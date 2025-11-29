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
namespace App\Core\Routing\Exceptions;

use App\Core\Error\Concerns\LogsExceptions;
use RuntimeException;

/**
 * Route Not Found Exception
 */
class RouteNotFoundException extends RuntimeException
{
    use LogsExceptions;

    protected string $path;
    protected string $method;

    public function __construct(string $path, string $method)
    {
        $this->path = $path;
        $this->method = $method;
        
        parent::__construct(sprintf('No route found for %s %s', $method, $path), 404);
        $this->logException('warning', 'Route not found', ['path' => $path, 'method' => $method]);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
