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
 * HTTP Response Interface
 *
 * Contract for HTTP response abstraction
 */
interface ResponseInterface
{
    /**
     * Set response content
     */
    public function setContent(string $content): static;

    /**
     * Get response content
     */
    public function getContent(): string;

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code, ?string $text = null): static;

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int;

    /**
     * Check if status is successful (2xx)
     */
    public function isSuccessful(): bool;

    /**
     * Check if status is redirect (3xx)
     */
    public function isRedirect(): bool;

    /**
     * Check if status is client error (4xx)
     */
    public function isClientError(): bool;

    /**
     * Check if status is server error (5xx)
     */
    public function isServerError(): bool;

    /**
     * Set response header
     */
    public function header(string $key, string $value, bool $replace = true): static;

    /**
     * Set multiple headers
     */
    public function withHeaders(array $headers): static;

    /**
     * Get header value
     */
    public function getHeader(string $key): ?string;

    /**
     * Get all headers
     */
    public function getHeaders(): array;

    /**
     * Send response to client
     */
    public function send(): static;

    /**
     * Send headers only
     */
    public function sendHeaders(): static;

    /**
     * Send content only
     */
    public function sendContent(): static;
}
