<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use App\Modules\Auth\Security\PasswordHasher;
use PHPUnit\Framework\TestCase;

/**
 * Password Hasher Unit Tests
 */
class PasswordHasherTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new PasswordHasher();
    }

    public function testHashCreatesValidHash(): void
    {
        $password = 'SecurePassword123!';
        $hash = $this->hasher->hash($password);

        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(strlen($hash) > 50); // Hashes are long
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $password = 'SecurePassword123!';
        $hash = $this->hasher->hash($password);

        $this->assertTrue($this->hasher->verify($password, $hash));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $password = 'SecurePassword123!';
        $hash = $this->hasher->hash($password);

        $this->assertFalse($this->hasher->verify('WrongPassword!', $hash));
    }

    public function testVerifyReturnsFalseForEmptyPassword(): void
    {
        $hash = $this->hasher->hash('SecurePassword123!');

        $this->assertFalse($this->hasher->verify('', $hash));
    }

    public function testNeedsRehashReturnsFalseForFreshHash(): void
    {
        $hash = $this->hasher->hash('SecurePassword123!');

        $this->assertFalse($this->hasher->needsRehash($hash));
    }

    public function testValidateStrengthReturnsErrorsForWeakPassword(): void
    {
        $result = $this->hasher->validateStrength('weak');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testValidateStrengthReturnsEmptyErrorsForStrongPassword(): void
    {
        // Must meet all requirements: 12+ chars, upper, lower, number, symbol
        $result = $this->hasher->validateStrength('MyStr0ng!Pass#2024');

        $this->assertIsArray($result);
        $this->assertTrue($result['valid'], 'Password should be valid');
        $this->assertEmpty($result['errors'], 'Strong password should have no errors: ' . json_encode($result['errors']));
    }

    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $hash1 = $this->hasher->hash('Password1!');
        $hash2 = $this->hasher->hash('Password2!');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testSamePasswordProducesDifferentHashes(): void
    {
        // Due to random salt, same password should produce different hashes
        $password = 'SecurePassword123!';
        $hash1 = $this->hasher->hash($password);
        $hash2 = $this->hasher->hash($password);

        $this->assertNotEquals($hash1, $hash2);
        // But both should verify correctly
        $this->assertTrue($this->hasher->verify($password, $hash1));
        $this->assertTrue($this->hasher->verify($password, $hash2));
    }
}
