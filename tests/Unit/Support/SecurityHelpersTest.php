<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for security-related helper functions
 */
class SecurityHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // csrf_token tests

    #[Test]
    public function csrf_token_returns_string(): void
    {
        $token = csrf_token();
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    #[Test]
    public function csrf_token_returns_same_token(): void
    {
        $token1 = csrf_token();
        $token2 = csrf_token();
        
        $this->assertSame($token1, $token2);
    }

    // csrf_field tests

    #[Test]
    public function csrf_field_returns_hidden_input(): void
    {
        $field = csrf_field();
        
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('csrf_token', $field);
    }

    // csrf_verify tests

    #[Test]
    public function csrf_verify_returns_true_for_valid_token(): void
    {
        $token = csrf_token();
        
        $this->assertTrue(csrf_verify($token));
    }

    #[Test]
    public function csrf_verify_returns_false_for_invalid_token(): void
    {
        csrf_token(); // Generate a token first
        
        $this->assertFalse(csrf_verify('invalid_token'));
    }

    // session tests

    #[Test]
    public function session_returns_manager_without_key(): void
    {
        $manager = session();
        
        $this->assertInstanceOf(\App\Core\Session\SessionManager::class, $manager);
    }

    #[Test]
    public function session_returns_value_with_key(): void
    {
        $_SESSION['test_key'] = 'test_value';
        
        $value = session('test_key');
        
        $this->assertSame('test_value', $value);
    }

    #[Test]
    public function session_returns_default_for_missing_key(): void
    {
        $value = session('nonexistent', 'default');
        
        $this->assertSame('default', $value);
    }

    // validator tests

    #[Test]
    public function validator_creates_instance(): void
    {
        $validator = validator(['name' => 'test']);
        
        $this->assertInstanceOf(\App\Core\Validation\Validator::class, $validator);
    }

    #[Test]
    public function validator_applies_rules(): void
    {
        $validator = validator(['email' => 'test@example.com'], ['email' => 'required|email']);
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validator_fails_with_invalid_data(): void
    {
        $validator = validator(['email' => 'invalid'], ['email' => 'required|email']);
        
        $this->assertTrue($validator->fails());
    }

    // Note: rate_limit and rate_limit_hit tests require Application instance
    // They are covered by integration tests
}
