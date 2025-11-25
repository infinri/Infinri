# Test Suite

**Phase 1 Test Coverage Target:** 95% minimum

## Running Tests

### Run all Phase 1 tests:
```bash
vendor/bin/phpunit --testsuite=Phase1
```

### Run unit tests only:
```bash
vendor/bin/phpunit --testsuite=Unit
```

### Run integration tests only:
```bash
vendor/bin/phpunit --testsuite=Integration
```

### Run with coverage report:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Run specific test file:
```bash
vendor/bin/phpunit tests/Unit/Container/ContainerTest.php
```

---

## Test Structure

```
tests/
├── bootstrap.php                      # Test setup
├── Unit/                              # Unit tests
│   ├── Container/
│   │   └── ContainerTest.php         # 25 tests (98% target)
│   ├── Config/
│   │   └── ConfigTest.php            # 18 tests (97% target)
│   ├── Log/
│   │   └── LoggerTest.php            # 18 tests (96% target)
│   ├── Support/
│   │   ├── EnvironmentTest.php       # 9 tests
│   │   └── HealthCheckTest.php       # 10 tests
│   └── ApplicationTest.php            # 19 tests
└── Integration/
    └── Phase1IntegrationTest.php      # 9 tests + performance validation
```

---

## Coverage Requirements

**Phase 1:**
- Container: 98% coverage
- Config: 97% coverage
- Overall: 95% minimum

**Performance Requirements (Integration Test):**
- Time limit: <50ms
- Memory limit: <10MB

---

## Test Counts

**Total Tests:** 111 tests

**By Category:**
- Container: 25 tests
- Config: 18 tests
- Logger: 18 tests
- Environment: 12 tests (includes boolean conversion)
- HealthCheck: 10 tests
- Application: 19 tests
- Integration: 9 tests

---

## Writing New Tests

### Test Naming Convention:
```php
/** @test */
public function it_does_something_specific(): void
{
    // Arrange
    $service = new Service();
    
    // Act
    $result = $service->doSomething();
    
    // Assert
    $this->assertEquals('expected', $result);
}
```

### Use Descriptive Names:
- ✅ `it_resolves_dependencies_automatically`
- ❌ `testResolve`

### Follow AAA Pattern:
1. **Arrange** - Set up test data
2. **Act** - Execute the code
3. **Assert** - Verify the result

---

## CI/CD Integration

Tests run automatically on:
- Every commit
- Every pull request
- Before deployment

**All tests must pass before merging.**
