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
namespace Tests\Unit\Security;

use App\Core\Authorization\AuthorizationException;
use App\Core\Authorization\Gate;
use App\Core\Authorization\Response;
use App\Core\Security\SafeRedirect;
use App\Modules\Auth\Security\LoginRateLimiter;
use App\Modules\Auth\Security\PasswordHasher;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\MockCache;
use Tests\Fixtures\MockPost;
use Tests\Fixtures\MockPostPolicy;
use Tests\Fixtures\MockUser;

class AuthorizationTest extends \Tests\TestCase
{
    // ─── Authorization Response ────────────────────────────────────

    #[Test]
    public function response_allow_creates_allowed_response(): void
    {
        $allow = Response::allow('Access granted');

        $this->assertTrue($allow->allowed());
        $this->assertFalse($allow->denied());
        $this->assertSame('Access granted', $allow->message());
    }

    #[Test]
    public function response_deny_creates_denied_response(): void
    {
        $deny = Response::deny('Access denied');

        $this->assertTrue($deny->denied());
        $this->assertFalse($deny->allowed());
        $this->assertSame('Access denied', $deny->message());
    }

    #[Test]
    public function response_deny_if_true_denies(): void
    {
        $this->assertTrue(Response::denyIf(true, 'Condition met')->denied());
    }

    #[Test]
    public function response_deny_if_false_allows(): void
    {
        $this->assertTrue(Response::denyIf(false)->allowed());
    }

    #[Test]
    public function response_allow_if_true_allows(): void
    {
        $this->assertTrue(Response::allowIf(true)->allowed());
    }

    #[Test]
    public function response_allow_if_false_denies(): void
    {
        $this->assertTrue(Response::allowIf(false, 'Condition not met')->denied());
    }

    // ─── Authorization Exception ───────────────────────────────────

    #[Test]
    public function exception_has_403_status(): void
    {
        $exception = new AuthorizationException('Custom message');

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('Custom message', $exception->getMessage());
    }

    #[Test]
    public function exception_has_default_message(): void
    {
        $exception = new AuthorizationException();

        $this->assertSame('This action is unauthorized.', $exception->getMessage());
    }

    #[Test]
    public function response_authorize_throws_on_denial(): void
    {
        $this->expectException(AuthorizationException::class);

        Response::deny('Test denial')->authorize();
    }

    // ─── Gate - Basic Abilities ────────────────────────────────────

    #[Test]
    public function gate_allows_defined_ability(): void
    {
        $gate = new Gate();
        $user = new MockUser(1, ['user']);
        $gate->setUserResolver(fn () => $user);

        $gate->define('view-dashboard', fn ($u) => $u !== null);

        $this->assertTrue($gate->allows('view-dashboard'));
    }

    #[Test]
    public function gate_allows_with_matching_argument(): void
    {
        $gate = new Gate();
        $user = new MockUser(1, ['user']);
        $gate->setUserResolver(fn () => $user);

        $gate->define('edit-profile', fn ($u, $profile) => $u?->getAuthIdentifier() === $profile['user_id']);

        $this->assertTrue($gate->allows('edit-profile', ['user_id' => 1]));
    }

    #[Test]
    public function gate_denies_with_non_matching_argument(): void
    {
        $gate = new Gate();
        $user = new MockUser(1, ['user']);
        $gate->setUserResolver(fn () => $user);

        $gate->define('edit-profile', fn ($u, $profile) => $u?->getAuthIdentifier() === $profile['user_id']);

        $this->assertTrue($gate->denies('edit-profile', ['user_id' => 2]));
    }

    #[Test]
    public function gate_denies_undefined_ability(): void
    {
        $gate = new Gate();
        $user = new MockUser(1, ['user']);
        $gate->setUserResolver(fn () => $user);

        $this->assertTrue($gate->denies('undefined-ability'));
    }

    // ─── Gate - Policies ───────────────────────────────────────────

    #[Test]
    public function policy_owner_can_view_post(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(1, ['user']));

        $this->assertTrue($gate->allows('view', new MockPost(100, 1)));
    }

    #[Test]
    public function policy_owner_can_update_post(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(1, ['user']));

        $this->assertTrue($gate->allows('update', new MockPost(100, 1)));
    }

    #[Test]
    public function policy_owner_can_delete_unpublished_post(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(1, ['user']));

        $this->assertTrue($gate->allows('delete', new MockPost(100, 1)));
    }

    #[Test]
    public function policy_owner_cannot_delete_published_post(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(1, ['user']));

        $this->assertTrue($gate->denies('delete', new MockPost(101, 1, true)));
    }

    #[Test]
    public function policy_other_can_view_but_not_update(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(2, ['user']));

        $this->assertTrue($gate->allows('view', new MockPost(100, 1)));
        $this->assertTrue($gate->denies('update', new MockPost(100, 1)));
    }

    #[Test]
    public function policy_admin_bypasses_all_checks(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => new MockUser(3, ['admin']));

        $this->assertTrue($gate->allows('update', new MockPost(100, 1)));
        $this->assertTrue($gate->allows('delete', new MockPost(101, 1, true)));
    }

    // ─── Gate - forUser() ──────────────────────────────────────────

    #[Test]
    public function gate_for_user_owner_allows_update(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => null);

        $this->assertTrue($gate->forUser(new MockUser(1))->allows('update', new MockPost(100, 1)));
    }

    #[Test]
    public function gate_for_user_other_denies_update(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => null);

        $this->assertTrue($gate->forUser(new MockUser(2))->denies('update', new MockPost(100, 1)));
    }

    #[Test]
    public function gate_for_user_null_denies_update(): void
    {
        $gate = $this->gateWithPolicy();
        $gate->setUserResolver(fn () => null);

        $this->assertTrue($gate->forUser(null)->denies('update', new MockPost(100, 1)));
    }

    // ─── SafeRedirect ──────────────────────────────────────────────

    #[Test]
    public function safe_redirect_allows_local_paths(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertSame('/', SafeRedirect::validate('/'));
        $this->assertSame('/dashboard', SafeRedirect::validate('/dashboard'));
        $this->assertSame('/user/profile', SafeRedirect::validate('/user/profile'));
    }

    #[Test]
    public function safe_redirect_blocks_external_urls(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertSame('/', SafeRedirect::validate('https://evil.com'));
        $this->assertSame('/', SafeRedirect::validate('http://evil.com/path'));
        $this->assertSame('/', SafeRedirect::validate('//evil.com'));
    }

    #[Test]
    public function safe_redirect_blocks_dangerous_schemes(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertSame('/', SafeRedirect::validate('javascript:alert(1)'));
        $this->assertSame('/', SafeRedirect::validate('data:text/html,<script>'));
    }

    #[Test]
    public function safe_redirect_blocks_empty_and_whitespace(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertSame('/', SafeRedirect::validate(''));
        $this->assertSame('/', SafeRedirect::validate('   '));
    }

    #[Test]
    public function safe_redirect_uses_custom_fallback(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertSame('/home', SafeRedirect::validate('https://evil.com', '/home'));
    }

    #[Test]
    public function safe_redirect_allows_whitelisted_domain(): void
    {
        SafeRedirect::clearAllowedDomains();
        SafeRedirect::allowDomain('trusted.com');

        $this->assertSame('https://trusted.com/callback', SafeRedirect::validate('https://trusted.com/callback'));
        $this->assertSame('/', SafeRedirect::validate('https://untrusted.com'));
    }

    #[Test]
    public function safe_redirect_allows_wildcard_subdomain(): void
    {
        SafeRedirect::clearAllowedDomains();
        SafeRedirect::allowDomain('*.example.com');

        $this->assertSame('https://api.example.com/auth', SafeRedirect::validate('https://api.example.com/auth'));
    }

    #[Test]
    public function safe_redirect_is_safe_helper(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertTrue(SafeRedirect::isSafe('/local'));
        $this->assertFalse(SafeRedirect::isSafe('https://evil.com'));
    }

    // ─── PasswordHasher ────────────────────────────────────────────

    #[Test]
    public function password_hasher_hashes_and_verifies(): void
    {
        $hasher = new PasswordHasher();
        $password = 'SecureP@ss123!';
        $hash = $hasher->hash($password);

        $this->assertNotEmpty($hash);
        $this->assertNotSame($password, $hash);
        $this->assertTrue($hasher->verify($password, $hash));
        $this->assertFalse($hasher->verify('wrong', $hash));
    }

    #[Test]
    public function password_hasher_returns_algorithm_name(): void
    {
        $hasher = new PasswordHasher();

        $this->assertNotEmpty($hasher->getAlgorithmName());
    }

    #[Test]
    public function password_hasher_rejects_weak_password(): void
    {
        $hasher = new PasswordHasher();
        $result = $hasher->validateStrength('123');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function password_hasher_accepts_strong_password(): void
    {
        $hasher = new PasswordHasher();
        $result = $hasher->validateStrength('SecureP@ssword123!');

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    #[Test]
    public function password_hasher_rejects_common_password(): void
    {
        $hasher = new PasswordHasher();
        $result = $hasher->validateStrength('Password1!');

        $this->assertFalse($result['valid']);
    }

    #[Test]
    public function password_hasher_detects_previous_password(): void
    {
        $hasher = new PasswordHasher();
        $oldHash = $hasher->hash('OldP@ssword123!');

        $this->assertTrue($hasher->wasUsedBefore('OldP@ssword123!', [$oldHash]));
        $this->assertFalse($hasher->wasUsedBefore('NewP@ssword456!', [$oldHash]));
    }

    #[Test]
    public function password_hasher_fresh_hash_does_not_need_rehash(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('SecureP@ss123!');

        $this->assertFalse($hasher->needsRehash($hash));
    }

    // ─── LoginRateLimiter ──────────────────────────────────────────

    #[Test]
    public function rate_limiter_allows_first_attempt(): void
    {
        $limiter = new LoginRateLimiter(new MockCache(), [
            'max_by_email' => 3,
            'max_by_ip' => 10,
            'decay_seconds' => 300,
        ]);

        $result = $limiter->check('192.168.1.1', 'test@example.com');

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    #[Test]
    public function rate_limiter_blocks_after_max_attempts(): void
    {
        $limiter = new LoginRateLimiter(new MockCache(), [
            'max_by_email' => 3,
            'max_by_ip' => 10,
            'decay_seconds' => 300,
        ]);

        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');

        $result = $limiter->check('192.168.1.1', 'test@example.com');

        $this->assertFalse($result['allowed']);
        $this->assertSame('too_many_attempts_email', $result['reason']);
    }

    #[Test]
    public function rate_limiter_no_remaining_attempts_after_max(): void
    {
        $limiter = new LoginRateLimiter(new MockCache(), [
            'max_by_email' => 3,
            'max_by_ip' => 10,
            'decay_seconds' => 300,
        ]);

        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');

        $this->assertSame(0, $limiter->remainingAttempts('test@example.com'));
    }

    #[Test]
    public function rate_limiter_clears_on_success(): void
    {
        $limiter = new LoginRateLimiter(new MockCache(), [
            'max_by_email' => 3,
            'max_by_ip' => 10,
            'decay_seconds' => 300,
        ]);

        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->recordFailure('192.168.1.1', 'test@example.com');
        $limiter->clearOnSuccess('192.168.1.1', 'test@example.com');

        $result = $limiter->check('192.168.1.1', 'test@example.com');
        $this->assertTrue($result['allowed']);
    }

    #[Test]
    public function rate_limiter_has_error_messages(): void
    {
        $this->assertNotEmpty(LoginRateLimiter::getErrorMessage('account_locked'));
        $this->assertNotEmpty(LoginRateLimiter::getErrorMessage('too_many_attempts_email'));
    }

    // ─── Helper Functions ──────────────────────────────────────────

    #[Test]
    public function gate_helper_returns_singleton(): void
    {
        $app = $this->app();
        $app->singleton(Gate::class, fn () => new Gate());

        $this->assertSame(gate(), gate());
    }

    #[Test]
    public function can_and_cannot_helpers_work(): void
    {
        $app = $this->app();
        $app->singleton(Gate::class, fn () => new Gate());

        gate()->define('test-ability', fn ($user) => true);

        $this->assertTrue(can('test-ability'));
        $this->assertFalse(cannot('test-ability'));
    }

    #[Test]
    public function is_safe_redirect_url_helper_works(): void
    {
        SafeRedirect::clearAllowedDomains();

        $this->assertTrue(is_safe_redirect_url('/local'));
    }

    // ─── Helpers ───────────────────────────────────────────────────

    private function gateWithPolicy(): Gate
    {
        $gate = new Gate();
        $gate->policy(MockPost::class, MockPostPolicy::class);

        return $gate;
    }
}
