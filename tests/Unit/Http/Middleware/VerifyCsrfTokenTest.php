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
namespace Tests\Unit\Http\Middleware;

use App\Core\Http\Middleware\VerifyCsrfToken;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\Csrf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VerifyCsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function get_requests_are_not_verified(): void
    {
        $middleware = new VerifyCsrfToken(new Csrf());
        $request = $this->createRequest('GET', '/');
        
        $called = false;
        $response = $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function head_requests_are_not_verified(): void
    {
        $middleware = new VerifyCsrfToken(new Csrf());
        $request = $this->createRequest('HEAD', '/');
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function options_requests_are_not_verified(): void
    {
        $middleware = new VerifyCsrfToken(new Csrf());
        $request = $this->createRequest('OPTIONS', '/');
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function excepted_paths_bypass_verification(): void
    {
        $middleware = new VerifyCsrfToken(new Csrf(), ['/api/*', '/webhook']);
        $request = $this->createRequest('POST', '/api/users');
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function exact_except_path_bypasses_verification(): void
    {
        $middleware = new VerifyCsrfToken(new Csrf(), ['/webhook']);
        $request = $this->createRequest('POST', '/webhook');
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function valid_token_passes_verification(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        $middleware = new VerifyCsrfToken($csrf);
        $request = $this->createRequest('POST', '/form', ['csrf_token' => $token]);
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function constructor_accepts_custom_csrf(): void
    {
        $csrf = $this->createMock(Csrf::class);
        $middleware = new VerifyCsrfToken($csrf, ['/test']);
        
        $this->assertInstanceOf(VerifyCsrfToken::class, $middleware);
    }

    #[Test]
    public function valid_token_in_header_passes_verification(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        $middleware = new VerifyCsrfToken($csrf);
        $request = $this->createRequestWithHeader('POST', '/form', 'X-CSRF-TOKEN', $token);
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    #[Test]
    public function valid_token_as_underscore_token_passes(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        $middleware = new VerifyCsrfToken($csrf);
        $request = $this->createRequest('POST', '/form', ['_token' => $token]);
        
        $called = false;
        $middleware->handle($request, function($req) use (&$called) {
            $called = true;
            return new Response('OK');
        });
        
        $this->assertTrue($called);
    }

    private function createRequest(string $method, string $path, array $input = []): Request
    {
        return new Request(
            [],           // query
            $input,       // request (POST data)
            [             // server
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $path,
            ],
            [],           // cookies
            null          // content
        );
    }

    private function createRequestWithHeader(string $method, string $path, string $header, string $value): Request
    {
        return new Request(
            [],           // query
            [],           // request (POST data)
            [             // server
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $path,
                'HTTP_' . str_replace('-', '_', strtoupper($header)) => $value,
            ],
            [],           // cookies
            null          // content
        );
    }
}
