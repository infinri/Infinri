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
namespace App\Core\Http;

/**
 * HTTP Status Codes
 * 
 * Centralized HTTP status code constants and text mappings.
 * Single source of truth for HTTP status information.
 */
final class HttpStatus
{
    // 1xx Informational
    public const CONTINUE = 100;
    public const SWITCHING_PROTOCOLS = 101;

    // 2xx Success
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NO_CONTENT = 204;

    // 3xx Redirection
    public const MOVED_PERMANENTLY = 301;
    public const FOUND = 302;
    public const SEE_OTHER = 303;
    public const NOT_MODIFIED = 304;
    public const TEMPORARY_REDIRECT = 307;
    public const PERMANENT_REDIRECT = 308;

    // 4xx Client Errors
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const PAGE_EXPIRED = 419;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    // 5xx Server Errors
    public const INTERNAL_SERVER_ERROR = 500;
    public const BAD_GATEWAY = 502;
    public const SERVICE_UNAVAILABLE = 503;

    /**
     * Status text mappings
     */
    private const TEXTS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Get status text for a code
     */
    public static function text(int $code): string
    {
        return self::TEXTS[$code] ?? 'Unknown Status';
    }

    /**
     * Check if status is informational (1xx)
     */
    public static function isInformational(int $code): bool
    {
        return $code >= 100 && $code < 200;
    }

    /**
     * Check if status is successful (2xx)
     */
    public static function isSuccessful(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    /**
     * Check if status is redirect (3xx)
     */
    public static function isRedirect(int $code): bool
    {
        return $code >= 300 && $code < 400;
    }

    /**
     * Check if status is client error (4xx)
     */
    public static function isClientError(int $code): bool
    {
        return $code >= 400 && $code < 500;
    }

    /**
     * Check if status is server error (5xx)
     */
    public static function isServerError(int $code): bool
    {
        return $code >= 500 && $code < 600;
    }

    /**
     * Check if response should have no body
     */
    public static function isEmpty(int $code): bool
    {
        return in_array($code, [self::NO_CONTENT, self::NOT_MODIFIED], true);
    }
}
