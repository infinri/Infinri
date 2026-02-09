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
namespace App\Core\Http;

use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\Concerns\HasStatusChecks;
use App\Core\Support\Str;

/**
 * HTTP Response
 *
 * Represents an HTTP response to be sent to the client.
 * Uses HttpStatus for centralized status code management.
 */
class Response implements ResponseInterface
{
    use HasStatusChecks;

    protected HeaderBag $headers;
    protected string $content = '';
    protected int $statusCode = HttpStatus::OK;
    protected string $statusText = 'OK';
    protected string $version = '1.1';
    protected bool $headersSent = false;

    /**
     * Cookies to send with response
     *
     * @var Cookie[]
     */
    protected array $cookies = [];

    /**
     * Create a new response instance
     */
    public function __construct(string $content = '', int $status = HttpStatus::OK, array $headers = [])
    {
        $this->headers = new HeaderBag($headers);
        $this->setContent($content);
        $this->setStatusCode($status);
    }

    /**
     * Create a response from content
     */
    public static function make(string $content = '', int $status = HttpStatus::OK, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Set response content
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code, ?string $text = null): static
    {
        $this->statusCode = $code;
        $this->statusText = $text ?? HttpStatus::text($code);

        return $this;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get HTTP status text
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * Set response header
     */
    public function header(string $key, string $value, bool $replace = true): static
    {
        $this->headers->set($key, $value, $replace);

        return $this;
    }

    /**
     * Set multiple headers
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->headers->set($key, $value);
        }

        return $this;
    }

    /**
     * Get header value
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers->get($key);
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers->toFlatArray();
    }

    /**
     * Get headers bag
     */
    public function getHeaderBag(): HeaderBag
    {
        return $this->headers;
    }

    /**
     * Set HTTP protocol version
     */
    public function setProtocolVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get HTTP protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Send response to client
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * Send headers only
     */
    public function sendHeaders(): static
    {
        if ($this->headersSent || headers_sent()) {
            return $this;
        }

        // Send cookies first (before header() calls)
        $this->sendCookies();

        header(sprintf('HTTP/%s %d %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        foreach ($this->headers->all() as $name => $values) {
            $name = Str::headerToHttpFormat($name);
            $replace = true;
            foreach ($values as $value) {
                header("$name: $value", $replace, $this->statusCode);
                $replace = false;
            }
        }

        $this->headersSent = true;

        return $this;
    }

    /**
     * Send content only
     */
    public function sendContent(): static
    {
        echo $this->content;

        return $this;
    }

    /**
     * Set Content-Type header to HTML
     */
    public function asHtml(): static
    {
        return $this->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Set Content-Type header to plain text
     */
    public function asText(): static
    {
        return $this->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Prepare response for caching
     */
    public function cache(int $seconds, bool $public = true): static
    {
        $directive = $public ? 'public' : 'private';

        return $this->header('Cache-Control', "$directive, max-age=$seconds");
    }

    /**
     * Prevent caching
     */
    public function noCache(): static
    {
        return $this->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // ==================== Cookies ====================

    /**
     * Add a cookie to the response
     */
    public function cookie(
        string $name,
        string $value = '',
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): static {
        $this->cookies[$name] = new Cookie(
            $name,
            $value,
            $minutes,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        );

        return $this;
    }

    /**
     * Add a Cookie object to the response
     */
    public function withCookie(Cookie $cookie): static
    {
        $this->cookies[$cookie->name] = $cookie;

        return $this;
    }

    /**
     * Add multiple cookies
     *
     * @param Cookie[] $cookies
     */
    public function withCookies(array $cookies): static
    {
        foreach ($cookies as $cookie) {
            $this->withCookie($cookie);
        }

        return $this;
    }

    /**
     * Remove a cookie (set expiration in the past)
     */
    public function forgetCookie(string $name, string $path = '/'): static
    {
        return $this->withCookie(Cookie::forget($name, $path));
    }

    /**
     * Get all cookies
     *
     * @return Cookie[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Check if response has a specific cookie
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Send all cookies
     */
    protected function sendCookies(): void
    {
        foreach ($this->cookies as $cookie) {
            $cookie->send();
        }
    }
}
