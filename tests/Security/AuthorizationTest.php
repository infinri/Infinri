<?php

declare(strict_types=1);

/**
 * Authorization & Security Tests
 * 
 * Tests all new security and authorization classes.
 * Run: php tests/Security/AuthorizationTest.php
 */

require_once __DIR__ . '/../../app/Core/Support/helpers.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../app/';
    
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Authorization\Gate;
use App\Core\Authorization\Policy;
use App\Core\Authorization\Response;
use App\Core\Authorization\AuthorizationException;
use App\Core\Contracts\Auth\AuthorizableInterface;
use App\Core\Security\SafeRedirect;
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Security\LoginRateLimiter;

/**
 * Simple test framework
 */
class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  ✓ {$message}\n";
        } else {
            $this->failed++;
            $this->failures[] = $message;
            echo "  ✗ {$message}\n";
        }
    }

    public function assertEqual(mixed $expected, mixed $actual, string $message): void
    {
        $this->assert($expected === $actual, "{$message} (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
    }

    public function assertNotEmpty(mixed $value, string $message): void
    {
        $this->assert(!empty($value), $message);
    }

    public function assertException(string $exceptionClass, callable $callback, string $message): void
    {
        try {
            $callback();
            $this->assert(false, "{$message} - No exception thrown");
        } catch (\Throwable $e) {
            $this->assert($e instanceof $exceptionClass, "{$message} - Got " . get_class($e));
        }
    }

    public function group(string $name, callable $tests): void
    {
        echo "\n📦 {$name}\n" . str_repeat('-', 50) . "\n";
        $tests($this);
    }

    public function summary(): int
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
        
        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - {$failure}\n";
            }
        }
        
        return $this->failed > 0 ? 1 : 0;
    }
}

/**
 * Mock User for testing
 */
class MockUser implements AuthorizableInterface
{
    public function __construct(
        public int $id,
        public array $roles = [],
        public array $permissions = []
    ) {}

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}

/**
 * Mock Post for testing policies
 */
class MockPost
{
    public function __construct(
        public int $id,
        public int $user_id,
        public bool $is_published = false
    ) {}
}

/**
 * Test Policy
 */
class MockPostPolicy extends Policy
{
    public function before(?AuthorizableInterface $user, string $ability): ?bool
    {
        // Admins can do anything
        if ($user?->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function view(?AuthorizableInterface $user, MockPost $post): bool
    {
        return true; // Anyone can view
    }

    public function update(?AuthorizableInterface $user, MockPost $post): bool
    {
        return $this->owns($user, $post);
    }

    public function delete(?AuthorizableInterface $user, MockPost $post): Response
    {
        if ($post->is_published) {
            return $this->deny('Cannot delete published posts');
        }
        return $this->allowIf($this->owns($user, $post), 'You do not own this post');
    }
}

/**
 * Mock Cache for LoginRateLimiter
 */
class MockCache implements \App\Core\Contracts\Cache\CacheInterface
{
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!isset($this->store[$key])) {
            $this->store[$key] = $value;
            return true;
        }
        return false;
    }

    public function forget(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }

    public function flush(): bool
    {
        $this->store = [];
        return true;
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function putMany(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }
        return true;
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        $current = (int) $this->get($key, 0);
        $this->put($key, $current + $value);
        return $current + $value;
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->put($key, $value, $ttl);
        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, null, $callback);
    }
}

// =============================================================================
// RUN TESTS
// =============================================================================

$test = new TestRunner();

echo "\n🧪 SECURITY & AUTHORIZATION TESTS\n";
echo str_repeat('=', 50) . "\n";

// -----------------------------------------------------------------------------
// Authorization Response Tests
// -----------------------------------------------------------------------------
$test->group('Authorization Response', function (TestRunner $t) {
    $allow = Response::allow('Access granted');
    $t->assert($allow->allowed(), 'Response::allow() creates allowed response');
    $t->assert(!$allow->denied(), 'Allowed response is not denied');
    $t->assertEqual('Access granted', $allow->message(), 'Allow message is set');

    $deny = Response::deny('Access denied');
    $t->assert($deny->denied(), 'Response::deny() creates denied response');
    $t->assert(!$deny->allowed(), 'Denied response is not allowed');
    $t->assertEqual('Access denied', $deny->message(), 'Deny message is set');

    $denyIf = Response::denyIf(true, 'Condition met');
    $t->assert($denyIf->denied(), 'denyIf(true) denies');

    $denyIfFalse = Response::denyIf(false);
    $t->assert($denyIfFalse->allowed(), 'denyIf(false) allows');

    $allowIf = Response::allowIf(true);
    $t->assert($allowIf->allowed(), 'allowIf(true) allows');

    $allowIfFalse = Response::allowIf(false, 'Condition not met');
    $t->assert($allowIfFalse->denied(), 'allowIf(false) denies');
});

// -----------------------------------------------------------------------------
// Authorization Exception Tests
// -----------------------------------------------------------------------------
$test->group('Authorization Exception', function (TestRunner $t) {
    $exception = new AuthorizationException('Custom message');
    $t->assertEqual(403, $exception->getStatusCode(), 'Exception has 403 status');
    $t->assertEqual('Custom message', $exception->getMessage(), 'Exception has custom message');

    $defaultException = new AuthorizationException();
    $t->assertEqual('This action is unauthorized.', $defaultException->getMessage(), 'Default message is set');

    // Test Response::authorize() throws
    $t->assertException(AuthorizationException::class, function () {
        Response::deny('Test denial')->authorize();
    }, 'Response::authorize() throws on denial');
});

// -----------------------------------------------------------------------------
// Gate Tests
// -----------------------------------------------------------------------------
$test->group('Gate - Basic Abilities', function (TestRunner $t) {
    $gate = new Gate();
    $user = new MockUser(1, ['user']);

    $gate->setUserResolver(fn() => $user);

    // Define simple ability
    $gate->define('view-dashboard', fn($u) => $u !== null);
    $t->assert($gate->allows('view-dashboard'), 'Allows defined ability');

    // Define ability with argument
    $gate->define('edit-profile', fn($u, $profile) => $u?->getAuthIdentifier() === $profile['user_id']);
    $t->assert($gate->allows('edit-profile', ['user_id' => 1]), 'Allows with matching user');
    $t->assert($gate->denies('edit-profile', ['user_id' => 2]), 'Denies with non-matching user');

    // Undefined ability
    $t->assert($gate->denies('undefined-ability'), 'Denies undefined ability');
});

// -----------------------------------------------------------------------------
// Gate with Policies
// -----------------------------------------------------------------------------
$test->group('Gate - Policies', function (TestRunner $t) {
    $gate = new Gate();
    $owner = new MockUser(1, ['user']);
    $other = new MockUser(2, ['user']);
    $admin = new MockUser(3, ['admin']);
    $post = new MockPost(100, 1);
    $publishedPost = new MockPost(101, 1, true);

    $gate->policy(MockPost::class, MockPostPolicy::class);

    // Test as owner
    $gate->setUserResolver(fn() => $owner);
    $t->assert($gate->allows('view', $post), 'Owner can view post');
    $t->assert($gate->allows('update', $post), 'Owner can update post');
    $t->assert($gate->allows('delete', $post), 'Owner can delete unpublished post');
    $t->assert($gate->denies('delete', $publishedPost), 'Owner cannot delete published post');

    // Test as non-owner
    $gate->setUserResolver(fn() => $other);
    $t->assert($gate->allows('view', $post), 'Other can view post');
    $t->assert($gate->denies('update', $post), 'Other cannot update post');

    // Test as admin (before() returns true)
    $gate->setUserResolver(fn() => $admin);
    $t->assert($gate->allows('update', $post), 'Admin can update any post');
    $t->assert($gate->allows('delete', $publishedPost), 'Admin can delete published post');
});

// -----------------------------------------------------------------------------
// Gate - forUser
// -----------------------------------------------------------------------------
$test->group('Gate - forUser()', function (TestRunner $t) {
    $gate = new Gate();
    $owner = new MockUser(1);
    $other = new MockUser(2);
    $post = new MockPost(100, 1);

    $gate->policy(MockPost::class, MockPostPolicy::class);

    // Check for specific user
    $t->assert($gate->forUser($owner)->allows('update', $post), 'forUser(owner) allows update');
    $t->assert($gate->forUser($other)->denies('update', $post), 'forUser(other) denies update');
    $t->assert($gate->forUser(null)->denies('update', $post), 'forUser(null) denies update');
});

// -----------------------------------------------------------------------------
// SafeRedirect Tests
// -----------------------------------------------------------------------------
$test->group('SafeRedirect', function (TestRunner $t) {
    SafeRedirect::clearAllowedDomains();

    // Local paths should be allowed
    $t->assertEqual('/', SafeRedirect::validate('/'), 'Root path allowed');
    $t->assertEqual('/dashboard', SafeRedirect::validate('/dashboard'), 'Local path allowed');
    $t->assertEqual('/user/profile', SafeRedirect::validate('/user/profile'), 'Nested local path allowed');

    // External URLs should be blocked
    $t->assertEqual('/', SafeRedirect::validate('https://evil.com'), 'External URL blocked');
    $t->assertEqual('/', SafeRedirect::validate('http://evil.com/path'), 'HTTP external URL blocked');
    $t->assertEqual('/', SafeRedirect::validate('//evil.com'), 'Protocol-relative URL blocked');

    // Dangerous schemes blocked
    $t->assertEqual('/', SafeRedirect::validate('javascript:alert(1)'), 'JavaScript URL blocked');
    $t->assertEqual('/', SafeRedirect::validate('data:text/html,<script>'), 'Data URL blocked');

    // Empty/invalid blocked
    $t->assertEqual('/', SafeRedirect::validate(''), 'Empty URL uses fallback');
    $t->assertEqual('/', SafeRedirect::validate('   '), 'Whitespace URL uses fallback');

    // Custom fallback
    $t->assertEqual('/home', SafeRedirect::validate('https://evil.com', '/home'), 'Custom fallback used');

    // Whitelist domains
    SafeRedirect::allowDomain('trusted.com');
    $t->assertEqual('https://trusted.com/callback', SafeRedirect::validate('https://trusted.com/callback'), 'Whitelisted domain allowed');
    $t->assertEqual('/', SafeRedirect::validate('https://untrusted.com'), 'Non-whitelisted still blocked');

    // Wildcard subdomain
    SafeRedirect::allowDomain('*.example.com');
    $t->assertEqual('https://api.example.com/auth', SafeRedirect::validate('https://api.example.com/auth'), 'Wildcard subdomain allowed');

    // isSafe helper
    $t->assert(SafeRedirect::isSafe('/local'), 'isSafe returns true for local');
    $t->assert(!SafeRedirect::isSafe('https://evil.com'), 'isSafe returns false for blocked');
});

// -----------------------------------------------------------------------------
// PasswordHasher Tests
// -----------------------------------------------------------------------------
$test->group('PasswordHasher', function (TestRunner $t) {
    $hasher = new PasswordHasher();

    // Hash and verify
    $password = 'SecureP@ss123!';
    $hash = $hasher->hash($password);
    $t->assertNotEmpty($hash, 'Hash is generated');
    $t->assert($hash !== $password, 'Hash is not plaintext');
    $t->assert($hasher->verify($password, $hash), 'Password verifies correctly');
    $t->assert(!$hasher->verify('wrong', $hash), 'Wrong password fails verification');

    // Algorithm detection
    $t->assertNotEmpty($hasher->getAlgorithmName(), 'Algorithm name returned');

    // Password strength validation
    $weak = $hasher->validateStrength('123');
    $t->assert(!$weak['valid'], 'Weak password rejected');
    $t->assert(count($weak['errors']) > 0, 'Weak password has errors');

    $strong = $hasher->validateStrength('SecureP@ssword123!');
    $t->assert($strong['valid'], 'Strong password accepted');
    $t->assertEqual([], $strong['errors'], 'Strong password has no errors');

    // Common password check (exact match only)
    $common = $hasher->validateStrength('Password1!'); // "password" is in common list
    $t->assert(!$common['valid'], 'Common password rejected');

    // Password history check
    $oldHash = $hasher->hash('OldP@ssword123!');
    $t->assert($hasher->wasUsedBefore('OldP@ssword123!', [$oldHash]), 'Previous password detected');
    $t->assert(!$hasher->wasUsedBefore('NewP@ssword456!', [$oldHash]), 'New password not in history');

    // Rehash check
    $t->assert(!$hasher->needsRehash($hash), 'Fresh hash does not need rehash');
});

// -----------------------------------------------------------------------------
// LoginRateLimiter Tests
// -----------------------------------------------------------------------------
$test->group('LoginRateLimiter', function (TestRunner $t) {
    $cache = new MockCache();
    $limiter = new LoginRateLimiter($cache, [
        'max_by_email' => 3,
        'max_by_ip' => 10,
        'decay_seconds' => 300,
    ]);

    $ip = '192.168.1.1';
    $email = 'test@example.com';

    // First attempts should be allowed
    $result = $limiter->check($ip, $email);
    $t->assert($result['allowed'], 'First attempt allowed');
    $t->assertEqual(null, $result['reason'], 'No reason for allowed');

    // Record failures
    $limiter->recordFailure($ip, $email);
    $limiter->recordFailure($ip, $email);
    $limiter->recordFailure($ip, $email);

    // After 3 failures, should be blocked
    $blocked = $limiter->check($ip, $email);
    $t->assert(!$blocked['allowed'], 'Blocked after max attempts');
    $t->assertEqual('too_many_attempts_email', $blocked['reason'], 'Blocked by email limit');

    // Remaining attempts
    $t->assertEqual(0, $limiter->remainingAttempts($email), 'No remaining attempts');

    // Clear on success
    $limiter->clearOnSuccess($ip, $email);
    $afterClear = $limiter->check($ip, $email);
    $t->assert($afterClear['allowed'], 'Allowed after clear');

    // Error messages
    $t->assertNotEmpty(LoginRateLimiter::getErrorMessage('account_locked'), 'Has account_locked message');
    $t->assertNotEmpty(LoginRateLimiter::getErrorMessage('too_many_attempts_email'), 'Has email limit message');
});

// -----------------------------------------------------------------------------
// Helper Functions Tests
// -----------------------------------------------------------------------------
$test->group('Helper Functions', function (TestRunner $t) {
    // gate() helper
    $gate1 = gate();
    $gate2 = gate();
    $t->assert($gate1 === $gate2, 'gate() returns singleton');

    // Define ability via helper
    gate()->define('test-ability', fn($user) => true);
    
    // can/cannot helpers
    $t->assert(can('test-ability'), 'can() helper works');
    $t->assert(!cannot('test-ability'), 'cannot() helper works');

    // SafeRedirect helpers
    $t->assert(is_safe_redirect_url('/local'), 'is_safe_redirect_url() helper works');
});

// Print summary
exit($test->summary());
