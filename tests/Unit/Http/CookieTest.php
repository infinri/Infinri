<?php declare(strict_types=1);

use App\Core\Http\Cookie;

describe('Cookie', function () {
    
    it('creates a cookie with defaults', function () {
        $cookie = new Cookie('test', 'value');
        
        expect($cookie->name)->toBe('test');
        expect($cookie->value)->toBe('value');
        expect($cookie->path)->toBe('/');
        expect($cookie->secure)->toBeTrue();
        expect($cookie->httpOnly)->toBeTrue();
        expect($cookie->sameSite)->toBe('Lax');
    });
    
    it('creates session cookie with zero expiration', function () {
        $cookie = Cookie::session('session_cookie', 'data');
        
        expect($cookie->expires)->toBe(0);
        expect($cookie->isSessionCookie())->toBeTrue();
    });
    
    it('creates forever cookie with 5 year expiration', function () {
        $cookie = Cookie::forever('remember', 'token');
        
        $fiveYearsFromNow = time() + (60 * 60 * 24 * 365 * 5);
        expect($cookie->expires)->toBeGreaterThan(time());
        expect($cookie->expires)->toBeLessThanOrEqual($fiveYearsFromNow + 60);
    });
    
    it('creates forget cookie with past expiration', function () {
        $cookie = Cookie::forget('old_cookie');
        
        expect($cookie->expires)->toBeLessThan(time());
        expect($cookie->value)->toBe(''); // Empty value
    });
    
    it('creates cookie from array options', function () {
        $cookie = Cookie::make('custom', 'value', [
            'minutes' => 60,
            'path' => '/admin',
            'domain' => 'example.com',
            'secure' => false,
            'httpOnly' => false,
            'sameSite' => 'Strict',
        ]);
        
        expect($cookie->path)->toBe('/admin');
        expect($cookie->domain)->toBe('example.com');
        expect($cookie->secure)->toBeFalse();
        expect($cookie->httpOnly)->toBeFalse();
        expect($cookie->sameSite)->toBe('Strict');
    });
    
    it('generates correct header value', function () {
        $cookie = new Cookie(
            'test',
            'value',
            60, // 1 hour
            '/',
            '',
            true,
            true,
            'Lax'
        );
        
        $header = $cookie->toHeaderValue();
        
        expect($header)->toContain('test=value');
        expect($header)->toContain('Path=/');
        expect($header)->toContain('Secure');
        expect($header)->toContain('HttpOnly');
        expect($header)->toContain('SameSite=Lax');
        expect($header)->toContain('Max-Age=');
    });
    
    it('converts to string as name=value', function () {
        $cookie = new Cookie('user', 'john');
        
        expect((string) $cookie)->toBe('user=john');
    });
    
    it('handles special characters in value', function () {
        $cookie = new Cookie('data', 'hello world');
        $header = $cookie->toHeaderValue();
        
        // urlencode uses + for spaces
        expect($header)->toContain('data=hello+world');
    });
    
    it('detects expired cookies', function () {
        // Use Cookie::forget which creates a truly expired cookie
        $cookie = Cookie::forget('old');
        
        expect($cookie->isExpired())->toBeTrue();
    });
    
    it('detects non-expired cookies', function () {
        $cookie = new Cookie('fresh', 'value', 60);
        
        expect($cookie->isExpired())->toBeFalse();
    });

    // Cookie Prefix Tests
    
    it('creates __Host- prefixed cookie', function () {
        $cookie = Cookie::host('session', 'abc123', 60);
        
        expect($cookie->name)->toBe('__Host-session');
        expect($cookie->secure)->toBeTrue();
        expect($cookie->domain)->toBe('');
        expect($cookie->path)->toBe('/');
        expect($cookie->isHostPrefixed())->toBeTrue();
    });
    
    it('creates __Secure- prefixed cookie', function () {
        $cookie = Cookie::secure('token', 'xyz789', 30);
        
        expect($cookie->name)->toBe('__Secure-token');
        expect($cookie->secure)->toBeTrue();
        expect($cookie->sameSite)->toBe('Strict');
        expect($cookie->isSecurePrefixed())->toBeTrue();
    });
    
    it('validates __Host- cookie requirements', function () {
        $validCookie = Cookie::host('test', 'value');
        expect($validCookie->validate())->toBeTrue();
    });
    
    it('throws on invalid __Host- cookie without secure', function () {
        $cookie = new Cookie('__Host-bad', 'value', 0, '/', '', false);
        
        expect(fn() => $cookie->validate())
            ->toThrow(\InvalidArgumentException::class, 'Secure flag');
    });
    
    it('throws on invalid __Host- cookie with domain', function () {
        $cookie = new Cookie('__Host-bad', 'value', 0, '/', 'example.com', true);
        
        expect(fn() => $cookie->validate())
            ->toThrow(\InvalidArgumentException::class, 'Domain');
    });
    
    it('throws on invalid __Host- cookie with wrong path', function () {
        $cookie = new Cookie('__Host-bad', 'value', 0, '/admin', '', true);
        
        expect(fn() => $cookie->validate())
            ->toThrow(\InvalidArgumentException::class, 'Path');
    });
    
    it('gets unprefixed name', function () {
        $hostCookie = Cookie::host('session', 'value');
        $secureCookie = Cookie::secure('token', 'value');
        $normalCookie = new Cookie('normal', 'value');
        
        expect($hostCookie->getUnprefixedName())->toBe('session');
        expect($secureCookie->getUnprefixedName())->toBe('token');
        expect($normalCookie->getUnprefixedName())->toBe('normal');
    });
});
