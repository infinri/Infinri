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

use App\Core\Http\Middleware\EncryptCookies;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Cookie;
use App\Core\Security\CookieEncrypter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EncryptCookiesTest extends TestCase
{
    private string $testKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testKey = CookieEncrypter::generateKey();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        $this->assertInstanceOf(EncryptCookies::class, $middleware);
    }

    #[Test]
    public function it_decrypts_incoming_cookies(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        // Encrypt a value
        $originalValue = 'secret_data';
        $encryptedValue = $encrypter->encrypt($originalValue);
        
        // Create request with encrypted cookie
        $request = Request::create('/test', 'GET', [], ['test_cookie' => $encryptedValue]);
        
        $decryptedRequest = null;
        $next = function ($req) use (&$decryptedRequest) {
            $decryptedRequest = $req;
            return new Response('OK');
        };
        
        $middleware->handle($request, $next);
        
        // The cookie should be decrypted
        $this->assertEquals($originalValue, $decryptedRequest->cookies->get('test_cookie'));
    }

    #[Test]
    public function it_encrypts_outgoing_cookies(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            $response->cookie('test_cookie', 'secret_value', 60);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        // The outgoing cookie should be encrypted
        $cookies = $response->getCookies();
        $this->assertArrayHasKey('test_cookie', $cookies);
        
        // The value should be encrypted (not the original)
        $cookieValue = $cookies['test_cookie']->value;
        $this->assertNotEquals('secret_value', $cookieValue);
        
        // But should decrypt back to original
        $decrypted = $encrypter->decrypt($cookieValue);
        $this->assertEquals('secret_value', $decrypted);
    }

    #[Test]
    public function it_handles_tampered_cookies_gracefully(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        // Create request with tampered/invalid cookie
        $request = Request::create('/test', 'GET', [], ['bad_cookie' => 'invalid_encrypted_data']);
        
        $decryptedRequest = null;
        $next = function ($req) use (&$decryptedRequest) {
            $decryptedRequest = $req;
            return new Response('OK');
        };
        
        // Should not throw - just pass through original value
        $middleware->handle($request, $next);
        
        // The original value should be preserved
        $this->assertEquals('invalid_encrypted_data', $decryptedRequest->cookies->get('bad_cookie'));
    }

    #[Test]
    public function it_excludes_disabled_cookies_from_encryption(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $encrypter->except(['unencrypted_cookie']);
        $middleware = new EncryptCookies($encrypter);
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            $response->cookie('unencrypted_cookie', 'plain_value', 60);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        // This cookie should NOT be encrypted
        $cookies = $response->getCookies();
        $this->assertEquals('plain_value', $cookies['unencrypted_cookie']->value);
    }

    #[Test]
    public function it_can_disable_encryption_for_cookies(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        $middleware->disableFor('csrf_token');
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            $response->cookie('csrf_token', 'token_value', 60);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        // This cookie should NOT be encrypted
        $cookies = $response->getCookies();
        $this->assertEquals('token_value', $cookies['csrf_token']->value);
    }

    #[Test]
    public function it_can_disable_multiple_cookies(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        $middleware->disableFor(['cookie1', 'cookie2']);
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            $response->cookie('cookie1', 'value1', 60);
            $response->cookie('cookie2', 'value2', 60);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        $cookies = $response->getCookies();
        $this->assertEquals('value1', $cookies['cookie1']->value);
        $this->assertEquals('value2', $cookies['cookie2']->value);
    }

    #[Test]
    public function it_preserves_cookie_attributes_after_encryption(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            // Create cookie with specific attributes
            $cookie = new Cookie(
                'test_cookie',
                'secret',
                60,
                '/api',
                'example.com',
                true,
                true,
                'Strict'
            );
            $response->withCookie($cookie);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        $cookies = $response->getCookies();
        $encryptedCookie = $cookies['test_cookie'];
        
        // All attributes should be preserved
        $this->assertEquals('/api', $encryptedCookie->path);
        $this->assertEquals('example.com', $encryptedCookie->domain);
        $this->assertTrue($encryptedCookie->secure);
        $this->assertTrue($encryptedCookie->httpOnly);
        $this->assertEquals('Strict', $encryptedCookie->sameSite);
    }

    #[Test]
    public function it_handles_response_without_cookies(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        $request = Request::create('/test', 'GET');
        
        // Response with no cookies
        $next = function ($req) {
            return new Response('OK');
        };
        
        $response = $middleware->handle($request, $next);
        
        // Should return response unchanged (no cookies to encrypt)
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('OK', $response->getContent());
        $this->assertEmpty($response->getCookies());
    }

    #[Test]
    public function it_handles_session_cookies_with_zero_expiration(): void
    {
        $encrypter = new CookieEncrypter($this->testKey);
        $middleware = new EncryptCookies($encrypter);
        
        $request = Request::create('/test', 'GET');
        
        $next = function ($req) {
            $response = new Response('OK');
            // Session cookie (minutes = 0)
            $response->cookie('session_cookie', 'session_data', 0);
            return $response;
        };
        
        $response = $middleware->handle($request, $next);
        
        $cookies = $response->getCookies();
        $sessionCookie = $cookies['session_cookie'];
        
        // Should be a session cookie (expires = 0)
        $this->assertEquals(0, $sessionCookie->expires);
        
        // Value should be encrypted
        $this->assertNotEquals('session_data', $sessionCookie->value);
    }
}
