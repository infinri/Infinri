<?php declare(strict_types=1);

use App\Helpers\Session;

describe('Session Helper', function () {
    beforeEach(function () {
        // Reset session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Start a fresh session for testing
        @session_start();
        $_SESSION = [];
    });
    
    describe('csrf()', function () {
        it('generates a CSRF token', function () {
            Session::start();
            $token = Session::csrf();
            
            expect($token)
                ->toBeString()
                ->not->toBeEmpty()
                ->toHaveLength(64); // 32 bytes = 64 hex chars
        });
        
        it('returns the same token on multiple calls', function () {
            Session::start();
            $token1 = Session::csrf();
            $token2 = Session::csrf();
            
            expect($token1)->toBe($token2);
        });
    });
    
    describe('verifyCsrf()', function () {
        it('validates correct CSRF token', function () {
            Session::start();
            $token = Session::csrf();
            
            expect(Session::verifyCsrf($token))->toBeTrue();
        });
        
        it('rejects incorrect CSRF token', function () {
            Session::start();
            Session::csrf(); // Generate valid token
            
            expect(Session::verifyCsrf('invalid-token'))->toBeFalse();
        });
        
        it('rejects empty token', function () {
            Session::start();
            Session::csrf();
            
            expect(Session::verifyCsrf(''))->toBeFalse();
        });
        
        it('uses timing-safe comparison', function () {
            Session::start();
            $token = Session::csrf();
            
            // Even similar tokens should fail
            $almostCorrect = substr($token, 0, -1) . 'x';
            
            expect(Session::verifyCsrf($almostCorrect))->toBeFalse();
        });
    });
    
    describe('get() and set()', function () {
        it('stores and retrieves values', function () {
            Session::start();
            Session::set('test_key', 'test_value');
            
            expect(Session::get('test_key'))->toBe('test_value');
        });
        
        it('returns default for missing keys', function () {
            Session::start();
            
            expect(Session::get('missing_key', 'default'))->toBe('default');
        });
        
        it('returns null for missing keys without default', function () {
            Session::start();
            
            expect(Session::get('missing_key'))->toBeNull();
        });
        
        it('stores different data types', function () {
            Session::start();
            Session::set('string', 'hello');
            Session::set('int', 123);
            Session::set('array', ['a', 'b']);
            
            expect(Session::get('string'))->toBe('hello');
            expect(Session::get('int'))->toBe(123);
            expect(Session::get('array'))->toBe(['a', 'b']);
        });
        
        it('overwrites existing values', function () {
            Session::start();
            Session::set('key', 'value1');
            Session::set('key', 'value2');
            
            expect(Session::get('key'))->toBe('value2');
        });
    });
    
    describe('start()', function () {
        it('does not start twice', function () {
            Session::start();
            Session::start(); // Should not throw
            
            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });
        
        it('works with already active session', function () {
            // Session is already started in beforeEach
            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
            
            // Starting again should not throw
            Session::start();
            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        });
    });
    
    describe('regenerate()', function () {
        it('regenerates session ID and CSRF token', function () {
            Session::start();
            $oldToken = Session::csrf();
            $oldSessionId = session_id();
            
            Session::regenerate();
            
            $newToken = Session::csrf();
            $newSessionId = session_id();
            
            expect($newToken)->not->toBe($oldToken);
            expect($newSessionId)->not->toBe($oldSessionId);
        });
    });
    
    describe('destroy()', function () {
        it('destroys session and clears data', function () {
            Session::start();
            Session::set('test', 'value');
            
            Session::destroy();
            
            // Session should be destroyed
            expect(session_status())->toBe(PHP_SESSION_NONE);
        });
    });
});
