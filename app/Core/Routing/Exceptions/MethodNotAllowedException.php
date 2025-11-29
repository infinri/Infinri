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
 * Method Not Allowed Exception
 */
class MethodNotAllowedException extends RuntimeException
{
    use LogsExceptions;

    protected string $path;
    protected string $method;
    protected array $allowedMethods;

    public function __construct(string $path, string $method, array $allowedMethods = [])
    {
        $this->path = $path;
        $this->method = $method;
        $this->allowedMethods = $allowedMethods;
        
        parent::__construct(
            sprintf('Method %s not allowed for %s. Allowed: %s', $method, $path, implode(', ', $allowedMethods)),
            405
        );
        
        $this->logException('warning', 'Method not allowed', [
            'path' => $path,
            'method' => $method,
            'allowed_methods' => $allowedMethods,
        ]);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
