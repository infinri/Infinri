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
namespace App\Core\Error;

use RuntimeException;

/**
 * HTTP Exception
 * 
 * Exception with HTTP status code for proper error responses.
 */
class HttpException extends RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Create a 400 Bad Request exception
     */
    public static function badRequest(string $message = 'Bad Request'): static
    {
        return new static(400, $message);
    }

    /**
     * Create a 401 Unauthorized exception
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return new static(401, $message);
    }

    /**
     * Create a 403 Forbidden exception
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return new static(403, $message);
    }

    /**
     * Create a 404 Not Found exception
     */
    public static function notFound(string $message = 'Not Found'): static
    {
        return new static(404, $message);
    }

    /**
     * Create a 405 Method Not Allowed exception
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed'): static
    {
        return new static(405, $message);
    }

    /**
     * Create a 422 Unprocessable Entity exception
     */
    public static function unprocessable(string $message = 'Unprocessable Entity'): static
    {
        return new static(422, $message);
    }

    /**
     * Create a 429 Too Many Requests exception
     */
    public static function tooManyRequests(string $message = 'Too Many Requests'): static
    {
        return new static(429, $message);
    }

    /**
     * Create a 500 Internal Server Error exception
     */
    public static function serverError(string $message = 'Internal Server Error'): static
    {
        return new static(500, $message);
    }

    /**
     * Create a 503 Service Unavailable exception
     */
    public static function serviceUnavailable(string $message = 'Service Unavailable'): static
    {
        return new static(503, $message);
    }
}
