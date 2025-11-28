<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Core\Security\Csrf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Initialize session array for testing
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function token_generates_token(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    #[Test]
    public function token_returns_same_token_on_subsequent_calls(): void
    {
        $csrf = new Csrf();
        $token1 = $csrf->token();
        $token2 = $csrf->token();
        
        $this->assertSame($token1, $token2);
    }

    #[Test]
    public function regenerate_creates_new_token(): void
    {
        $csrf = new Csrf();
        $token1 = $csrf->token();
        $token2 = $csrf->regenerate();
        
        $this->assertNotSame($token1, $token2);
    }

    #[Test]
    public function verify_returns_true_for_valid_token(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        $this->assertTrue($csrf->verify($token));
    }

    #[Test]
    public function verify_returns_false_for_invalid_token(): void
    {
        $csrf = new Csrf();
        $csrf->token();
        
        $this->assertFalse($csrf->verify('invalid_token'));
    }

    #[Test]
    public function verify_returns_false_when_no_token_exists(): void
    {
        $csrf = new Csrf();
        $this->assertFalse($csrf->verify('any_token'));
    }

    #[Test]
    public function field_returns_hidden_input(): void
    {
        $csrf = new Csrf();
        $field = $csrf->field();
        
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    #[Test]
    public function meta_returns_meta_tag(): void
    {
        $csrf = new Csrf();
        $meta = $csrf->meta();
        
        $this->assertStringContainsString('<meta name="csrf-token"', $meta);
        $this->assertStringContainsString('content="', $meta);
    }

    #[Test]
    public function constructor_accepts_custom_lifetime(): void
    {
        $csrf = new Csrf(3600);
        $token = $csrf->token();
        
        $this->assertNotEmpty($token);
        $this->assertTrue($csrf->verify($token));
    }

    #[Test]
    public function verify_returns_false_when_token_expired(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        // Manually expire the token (note: key is _csrf_token)
        $_SESSION['_csrf_token']['expires'] = time() - 100;
        
        $this->assertFalse($csrf->verify($token));
    }

    #[Test]
    public function verify_returns_false_when_expires_key_missing(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();
        
        // Remove the expires key (note: key is _csrf_token)
        unset($_SESSION['_csrf_token']['expires']);
        
        $this->assertFalse($csrf->verify($token));
    }
}
