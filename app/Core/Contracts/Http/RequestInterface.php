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
namespace App\Core\Contracts\Http;

/**
 * HTTP Request Interface
 *
 * Contract for HTTP request abstraction
 */
interface RequestInterface
{
    /**
     * Create request from PHP globals
     */
    public static function capture(): static;

    /**
     * Get HTTP method
     */
    public function method(): string;

    /**
     * Get request path (without query string)
     */
    public function path(): string;

    /**
     * Get full URL without query string
     */
    public function url(): string;

    /**
     * Get full URL with query string
     */
    public function fullUrl(): string;

    /**
     * Get input value from query or body
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Get query string parameter
     */
    public function query(string $key, mixed $default = null): mixed;

    /**
     * Get POST body parameter
     */
    public function post(string $key, mixed $default = null): mixed;

    /**
     * Get all input data
     */
    public function all(): array;

    /**
     * Get subset of input data
     */
    public function only(array $keys): array;

    /**
     * Get all input except specified keys
     */
    public function except(array $keys): array;

    /**
     * Check if input key exists
     */
    public function has(string $key): bool;

    /**
     * Check if input exists and is not empty
     */
    public function filled(string $key): bool;

    /**
     * Get header value
     */
    public function header(string $key, mixed $default = null): mixed;

    /**
     * Get all headers
     */
    public function headers(): array;

    /**
     * Get bearer token from Authorization header
     */
    public function bearerToken(): ?string;

    /**
     * Get client IP address
     */
    public function ip(): string;

    /**
     * Get user agent string
     */
    public function userAgent(): ?string;

    /**
     * Check HTTP method
     */
    public function isMethod(string $method): bool;

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool;

    /**
     * Check if request is AJAX
     */
    public function ajax(): bool;

    /**
     * Check if request is secure (HTTPS)
     */
    public function secure(): bool;

    /**
     * Get route parameters
     */
    public function route(?string $key = null, mixed $default = null): mixed;

    /**
     * Set route parameters
     */
    public function setRouteParameters(array $parameters): static;

    /**
     * Get all query string parameters
     */
    public function getQueryParams(): array;

    /**
     * Get all cookies
     */
    public function getCookies(): array;
}
