<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Core\Contracts\Auth\AuthorizableInterface;
use PHPUnit\Framework\TestCase;

/**
 * User Model Unit Tests
 */
class UserModelTest extends TestCase
{
    public function testUserImplementsAuthenticatableInterface(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(AuthenticatableInterface::class, $user);
    }

    public function testUserImplementsAuthorizableInterface(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(AuthorizableInterface::class, $user);
    }

    public function testGetRememberTokenNameReturnsCorrectColumn(): void
    {
        $user = new User();
        
        $this->assertEquals('remember_token', $user->getRememberTokenName());
    }

    public function testHasRolesTraitMethods(): void
    {
        $user = new User();
        
        // Test role methods exist and return correct types
        $this->assertIsArray($user->getRoles());
        $this->assertIsBool($user->hasRole('admin'));
        $this->assertIsArray($user->getPermissions());
        $this->assertIsBool($user->hasPermission('edit-posts'));
    }

    public function testHasTwoFactorEnabledReturnsFalseByDefault(): void
    {
        $user = new User();
        
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function testHasVerifiedEmailReturnsFalseByDefault(): void
    {
        $user = new User();
        
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function testGetTwoFactorRecoveryCodesReturnsArrayByDefault(): void
    {
        $user = new User();
        
        $this->assertIsArray($user->getTwoFactorRecoveryCodes());
        $this->assertEmpty($user->getTwoFactorRecoveryCodes());
    }

    public function testIsSuperAdminReturnsFalseByDefault(): void
    {
        $user = new User();
        
        $this->assertFalse($user->isSuperAdmin());
    }

    public function testIsAdminReturnsFalseByDefault(): void
    {
        $user = new User();
        
        $this->assertFalse($user->isAdmin());
    }
}
