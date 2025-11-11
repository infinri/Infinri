# 🧪 Portfolio Testing Suite

Comprehensive test suite for the Portfolio project using **Pest PHP** - a delightful testing framework with a focus on simplicity.

---

## 📦 Setup

### 1. Install Dependencies

```bash
composer install
```

This will install:
- **Pest** - Testing framework
- **PHPUnit** - Test runner (used by Pest)
- **Mockery** - Mocking library
- **PHPStan** - Static analysis
- **PHP_CodeSniffer** - Code style checker

---

## 🚀 Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suites
```bash
# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# Parallel execution (faster)
composer test:parallel
```

### Run Individual Test Files
```bash
vendor/bin/pest tests/Unit/Helpers/EscTest.php
vendor/bin/pest tests/Unit/Core/RouterTest.php
```

### Watch Mode (re-run on file changes)
```bash
vendor/bin/pest --watch
```

---

## 📊 Code Coverage

### Generate Coverage Report
```bash
composer test:coverage
```

This requires **Xdebug** or **PCOV** extension.

### Install Xdebug (if needed)
```bash
# Ubuntu/Debian
sudo apt-get install php-xdebug

# macOS (Homebrew)
pecl install xdebug
```

### View HTML Coverage Report
```bash
vendor/bin/pest --coverage --coverage-html=coverage
open coverage/index.html
```

---

## 🔍 Static Analysis

### Run PHPStan (Level 8)
```bash
composer phpstan
```

PHPStan checks for:
- Type errors
- Undefined variables
- Incorrect method calls
- Missing return types
- Dead code

---

## 📝 Code Style

### Check Code Style (PSR-12)
```bash
composer lint
```

### Auto-Fix Code Style Issues
```bash
composer lint:fix
```

---

## ✅ Run Everything (Quality Check)
```bash
composer quality
```

This runs:
1. Code style check (PHPCS)
2. Static analysis (PHPStan)
3. Full test suite (Pest)

---

## 📁 Test Structure

```
tests/
├── Pest.php                    # Pest configuration & global helpers
├── Unit/                       # Unit tests (isolated)
│   ├── Core/
│   │   └── RouterTest.php
│   └── Helpers/
│       ├── CacheTest.php
│       ├── EscTest.php
│       ├── PathTest.php
│       ├── SessionTest.php
│       ├── StrTest.php
│       └── ValidateTest.php
└── Integration/                # Integration tests (multiple components)
    └── (to be added)
```

---

## 🎯 Test Coverage Status

| Component | Tests | Status |
|-----------|-------|--------|
| **Esc Helper** | 13 tests | ✅ Complete |
| **Session Helper** | 9 tests | ✅ Complete |
| **Router** | 7 tests | ✅ Complete |
| **Path Helper** | 7 tests | ✅ Complete |
| **Str Helper** | 10 tests | ✅ Complete |
| **Validate Helper** | 5 tests | ✅ Complete |
| **Cache Helper** | 5 tests | ✅ Complete |
| **Total** | **56 tests** | ✅ **Ready** |

---

## 🧩 Writing Tests

### Pest Syntax (BDD Style)

```php
<?php declare(strict_types=1);

use App\Helpers\Esc;

describe('Esc Helper', function () {
    it('escapes HTML', function () {
        $input = '<script>alert("XSS")</script>';
        $output = Esc::html($input);
        
        expect($output)->not->toContain('<script>');
    });
    
    it('handles empty strings', function () {
        expect(Esc::html(''))->toBe('');
    });
});
```

### Available Expectations

```php
expect($value)->toBe('expected');
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->toBeEmpty();
expect($value)->toContain('substring');
expect($value)->toHaveLength(10);
expect($value)->toBeInstanceOf(Router::class);
expect($value)->toHaveKey('key');
expect($value)->toMatch('/regex/');
expect(fn() => dangerousFunction())->toThrow(Exception::class);
```

### Test Hooks

```php
beforeEach(function () {
    // Run before each test
    Cache::clear();
});

afterEach(function () {
    // Run after each test
    session_destroy();
});
```

---

## 🐛 Debugging Tests

### Run Tests with Verbose Output
```bash
vendor/bin/pest --verbose
```

### Stop on First Failure
```bash
vendor/bin/pest --stop-on-failure
```

### Filter Tests by Name
```bash
vendor/bin/pest --filter="escapes HTML"
```

### Show Test Execution Time
```bash
vendor/bin/pest --profile
```

---

## 🎓 Best Practices

### ✅ DO
- Write tests for all new features
- Test edge cases (empty strings, null, invalid input)
- Use descriptive test names
- Keep tests simple and focused
- Mock external dependencies
- Test both success and failure paths

### ❌ DON'T
- Test framework code (e.g., built-in functions)
- Write tests that depend on each other
- Hard-code file paths or credentials
- Skip writing tests for "simple" code
- Leave failing tests commented out

---

## 🔧 Troubleshooting

### "Class not found"
Make sure autoloader is up to date:
```bash
composer dump-autoload
```

### "Session already started"
Add to test's `beforeEach`:
```php
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
$_SESSION = [];
```

### PHPStan Errors
Check `phpstan.neon` for ignored patterns or adjust analysis level.

---

## 📚 Resources

- [Pest Documentation](https://pestphp.com/docs)
- [PHPUnit Assertions](https://phpunit.de/manual/current/en/assertions.html)
- [PHPStan Rules](https://phpstan.org/rules)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

---

## 🎉 Happy Testing!

Remember: **Good tests prevent bugs, great tests document behavior.**
