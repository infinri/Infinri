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
use App\Core\Http\Cookie;
use App\Core\Security\CookieEncrypter;
use Closure;
use Throwable;

/**
 * Encrypt Cookies Middleware
 *
 * Automatically decrypts incoming cookies and encrypts outgoing cookies.
 * Provides enterprise-grade cookie security with tamper detection.
 */
class EncryptCookies implements MiddlewareInterface
{
    /**
     * Cookie encrypter instance
     */
    protected CookieEncrypter $encrypter;

    /**
     * Cookies that should not be encrypted
     *
     * @var string[]
     */
    protected array $except = [
        // Add cookie names that should remain unencrypted
        // e.g., 'XSRF-TOKEN' if you need JS access to CSRF token
    ];

    public function __construct(?CookieEncrypter $encrypter = null)
    {
        $this->encrypter = $encrypter ?? new CookieEncrypter();
        $this->encrypter->except($this->except);
    }

    /**
     * Handle the request
     */
    public function handle(RequestInterface $request, Closure $next): ResponseInterface
    {
        // Decrypt incoming cookies
        $request = $this->decryptCookies($request);

        // Process request
        $response = $next($request);

        // Encrypt outgoing cookies
        return $this->encryptCookies($response);
    }

    /**
     * Decrypt cookies from incoming request
     */
    protected function decryptCookies(RequestInterface $request): RequestInterface
    {
        $decrypted = [];

        foreach ($request->cookies->all() as $name => $value) {
            if ($this->isDisabled($name)) {
                $decrypted[$name] = $value;
                continue;
            }

            try {
                $decryptedValue = $this->encrypter->decrypt($value);
                $decrypted[$name] = $decryptedValue ?? $value;
            } catch (Throwable $e) {
                // If decryption fails, cookie might be tampered, old, or invalid
                // Log for security monitoring, pass through original
                logger()->warning('Cookie decryption failed (possible tampering)', ['cookie' => $name, 'error' => $e->getMessage()]);
                $decrypted[$name] = $value;
            }
        }

        // Update request cookies with decrypted values
        return $this->updateRequestCookies($request, $decrypted);
    }

    /**
     * Encrypt cookies in outgoing response
     */
    protected function encryptCookies(ResponseInterface $response): ResponseInterface
    {
        if (! method_exists($response, 'getCookies')) {
            return $response;
        }

        $cookies = $response->getCookies();

        foreach ($cookies as $cookie) {
            if ($this->isDisabled($cookie->name)) {
                continue;
            }

            // Create encrypted cookie preserving original attributes
            $encryptedValue = $this->encrypter->encrypt($cookie->value);

            if (method_exists($response, 'withCookie')) {
                $response = $response->withCookie($this->createEncryptedCookie($cookie, $encryptedValue));
            }
        }

        return $response;
    }

    /**
     * Create an encrypted cookie preserving original attributes
     */
    protected function createEncryptedCookie(Cookie $original, string $encryptedValue): Cookie
    {
        // Calculate remaining minutes from expiration
        $minutes = 0;
        if ($original->expires > 0) {
            $minutes = (int) ceil(($original->expires - time()) / 60);
        }

        return new Cookie(
            $original->name,
            $encryptedValue,
            $minutes,
            $original->path,
            $original->domain,
            $original->secure,
            $original->httpOnly,
            $original->sameSite
        );
    }

    /**
     * Check if encryption is disabled for a cookie
     */
    protected function isDisabled(string $name): bool
    {
        return ! $this->encrypter->shouldEncrypt($name);
    }

    /**
     * Update request with decrypted cookies
     *
     * Note: This requires Request to support cookie modification.
     * For now, we'll update the underlying ParameterBag.
     */
    protected function updateRequestCookies(RequestInterface $request, array $cookies): RequestInterface
    {
        // If the request object supports setting cookies, use that
        if (method_exists($request, 'setCookies')) {
            return $request->setCookies($cookies);
        }

        // Otherwise, directly update the cookies ParameterBag
        if (property_exists($request, 'cookies')) {
            foreach ($cookies as $name => $value) {
                $request->cookies->set($name, $value);
            }
        }

        return $request;
    }

    /**
     * Add cookies to the exception list
     */
    public function disableFor(string|array $cookies): static
    {
        $cookies = is_array($cookies) ? $cookies : [$cookies];
        $this->encrypter->except($cookies);

        return $this;
    }
}
