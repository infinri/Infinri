<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;

/**
 * Security Headers Middleware
 * 
 * Adds security-related HTTP headers to responses.
 * Configurable for different security requirements.
 */
class SecurityHeadersMiddleware
{
    protected array $headers = [];
    protected bool $enableHsts = false;
    protected ?string $cspNonce = null;

    public function __construct(array $options = [])
    {
        $this->enableHsts = $options['hsts'] ?? false;
        $this->cspNonce = $options['csp_nonce'] ?? null;
        
        $this->headers = array_merge($this->getDefaultHeaders(), $options['headers'] ?? []);
    }

    /**
     * Handle the request
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $next($request);

        return $this->addSecurityHeaders($response, $request);
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        foreach ($this->headers as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            // Replace placeholders
            if (is_string($value)) {
                $value = $this->replacePlaceholders($value);
            }

            if (method_exists($response, 'withHeader')) {
                $response = $response->withHeader($name, $value);
            } else {
                header("{$name}: {$value}");
            }
        }

        // Add HSTS if enabled and request is HTTPS
        if ($this->enableHsts && $request->isSecure()) {
            $hsts = 'max-age=31536000; includeSubDomains';
            if (method_exists($response, 'withHeader')) {
                $response = $response->withHeader('Strict-Transport-Security', $hsts);
            } else {
                header("Strict-Transport-Security: {$hsts}");
            }
        }

        return $response;
    }

    /**
     * Replace placeholders in header values
     */
    protected function replacePlaceholders(string $value): string
    {
        if ($this->cspNonce && str_contains($value, '{nonce}')) {
            $value = str_replace('{nonce}', $this->cspNonce, $value);
        }

        return $value;
    }

    /**
     * Get default security headers
     */
    protected function getDefaultHeaders(): array
    {
        return [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Prevent clickjacking
            'X-Frame-Options' => 'DENY',
            
            // XSS Protection (legacy, but still useful)
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions policy (disable dangerous features)
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            
            // Prevent caching of sensitive pages (can be overridden per-route)
            // 'Cache-Control' => 'no-store, no-cache, must-revalidate',
            
            // Cross-Origin policies
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];
    }

    /**
     * Generate a CSP nonce
     */
    public static function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Build a Content-Security-Policy header value
     */
    public static function buildCsp(array $directives, ?string $nonce = null): string
    {
        $defaults = [
            'default-src' => "'self'",
            'script-src' => "'self'" . ($nonce ? " 'nonce-{$nonce}'" : ''),
            'style-src' => "'self'" . ($nonce ? " 'nonce-{$nonce}'" : ''),
            'img-src' => "'self' data:",
            'font-src' => "'self'",
            'connect-src' => "'self'",
            'frame-ancestors' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
        ];

        $merged = array_merge($defaults, $directives);
        $parts = [];

        foreach ($merged as $directive => $value) {
            if ($value !== null && $value !== false) {
                $parts[] = "{$directive} {$value}";
            }
        }

        return implode('; ', $parts);
    }
}
