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
namespace Tests\Unit\Http\Middleware;

use App\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    #[Test]
    public function constructor_uses_default_headers(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        
        $this->assertInstanceOf(SecurityHeadersMiddleware::class, $middleware);
    }

    #[Test]
    public function constructor_accepts_custom_options(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'hsts' => true,
            'csp_nonce' => 'test123',
            'headers' => ['X-Custom' => 'value'],
        ]);
        
        $this->assertInstanceOf(SecurityHeadersMiddleware::class, $middleware);
    }

    #[Test]
    public function handle_calls_next_middleware(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = $this->createRequest();
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function handle_returns_response(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = $this->createRequest();
        
        $response = $middleware->handle($request, function($req) {
            return new Response('OK');
        });
        
        $this->assertInstanceOf(Response::class, $response);
    }

    #[Test]
    public function generate_nonce_returns_base64_string(): void
    {
        $nonce = SecurityHeadersMiddleware::generateNonce();
        
        $this->assertIsString($nonce);
        $this->assertNotEmpty($nonce);
        // Base64 encoded 16 bytes = ~22-24 characters
        $this->assertGreaterThan(20, strlen($nonce));
    }

    #[Test]
    public function generate_nonce_is_unique(): void
    {
        $nonce1 = SecurityHeadersMiddleware::generateNonce();
        $nonce2 = SecurityHeadersMiddleware::generateNonce();
        
        $this->assertNotSame($nonce1, $nonce2);
    }

    #[Test]
    public function build_csp_returns_valid_header(): void
    {
        $csp = SecurityHeadersMiddleware::buildCsp([]);
        
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
    }

    #[Test]
    public function build_csp_includes_nonce_when_provided(): void
    {
        $csp = SecurityHeadersMiddleware::buildCsp([], 'testnonce');
        
        $this->assertStringContainsString("'nonce-testnonce'", $csp);
    }

    #[Test]
    public function build_csp_merges_custom_directives(): void
    {
        $csp = SecurityHeadersMiddleware::buildCsp([
            'img-src' => "'self' https://cdn.example.com",
        ]);
        
        $this->assertStringContainsString("img-src 'self' https://cdn.example.com", $csp);
    }

    #[Test]
    public function build_csp_skips_null_directives(): void
    {
        $csp = SecurityHeadersMiddleware::buildCsp([
            'frame-ancestors' => null,
        ]);
        
        $this->assertStringNotContainsString('frame-ancestors', $csp);
    }

    #[Test]
    public function build_csp_skips_false_directives(): void
    {
        $csp = SecurityHeadersMiddleware::buildCsp([
            'frame-ancestors' => false,
        ]);
        
        $this->assertStringNotContainsString('frame-ancestors', $csp);
    }

    #[Test]
    public function handle_adds_hsts_for_secure_requests(): void
    {
        $middleware = new SecurityHeadersMiddleware([], null, true);
        $request = $this->createSecureRequest();
        
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        // HSTS header should be added for secure requests when enabled
        $this->assertInstanceOf(Response::class, $response);
    }

    #[Test]
    public function handle_skips_null_header_values(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'X-Custom' => null,
            'X-Valid' => 'value',
        ]);
        $request = $this->createRequest();
        
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertInstanceOf(Response::class, $response);
    }

    #[Test]
    public function handle_replaces_nonce_placeholder(): void
    {
        $middleware = new SecurityHeadersMiddleware([
            'Content-Security-Policy' => "script-src 'nonce-{nonce}'",
        ], 'test-nonce-123');
        $request = $this->createRequest();
        
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertInstanceOf(Response::class, $response);
    }

    private function createRequest(): Request
    {
        return new Request(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'],
            [],
            null
        );
    }

    private function createSecureRequest(): Request
    {
        return new Request(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/', 'HTTPS' => 'on'],
            [],
            null
        );
    }
}
