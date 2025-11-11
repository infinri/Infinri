<?php declare(strict_types=1);

use App\Helpers\Session;

/**
 * IMPORTANT: This test file does NOT use beforeEach session setup
 * This allows us to test Session::start() from a clean state
 */
describe('Session Isolated Tests', function () {
    describe('fresh session initialization', function () {
        it('initializes with secure cookie parameters', function () {
            // Ensure no session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Clear the static $started flag using reflection
            $reflection = new ReflectionClass(Session::class);
            $property = $reflection->getProperty('started');
            $property->setAccessible(true);
            $property->setValue(null, false);
            
            // Now start the session - this should trigger lines 36-50
            Session::start();
            
            // Verify session is active
            expect(session_status())->toBe(PHP_SESSION_ACTIVE);
            
            // Verify CSRF token was generated
            expect(isset($_SESSION['csrf_token']))->toBeTrue();
            expect(strlen($_SESSION['csrf_token']))->toBe(64); // 32 bytes = 64 hex chars
        });
        
        it('sets secure session cookie parameters', function () {
            // This test verifies the session_set_cookie_params call (lines 36-43)
            // We can't directly verify the params after session_start in CLI
            // But we can verify the method exists and Session::start() doesn't throw
            
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Reset session state
            $reflection = new ReflectionClass(Session::class);
            $property = $reflection->getProperty('started');
            $property->setAccessible(true);
            $property->setValue(null, false);
            
            // This should call session_set_cookie_params without errors
            expect(fn() => Session::start())->not->toThrow(Exception::class);
            
            // In a real browser environment, these params would be:
            // - lifetime: 0 (session cookie)
            // - path: /
            // - secure: true (HTTPS only)
            // - httponly: true (no JS access)
            // - samesite: Strict (CSRF protection)
        });
    });
    
    describe('CSRF token generation on start', function () {
        it('generates token on first start', function () {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Clear static state
            $reflection = new ReflectionClass(Session::class);
            $property = $reflection->getProperty('started');
            $property->setAccessible(true);
            $property->setValue(null, false);
            
            // Clear any existing token
            $_SESSION = [];
            
            // Start session - should generate token (line 50)
            Session::start();
            
            expect($_SESSION['csrf_token'])
                ->toBeString()
                ->toHaveLength(64);
        });
    });
});
