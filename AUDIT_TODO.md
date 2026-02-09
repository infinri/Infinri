# Infinri Framework — Audit Fix TODO

Generated from the security audit completed on 2025-02-09.

---

## 🔴 Critical (Security)

### 1. ✅ `FileStore` uses raw `unserialize()`
- **File:** `app/Core/Cache/FileStore.php` (~line 152)
- **Issue:** `unserialize()` on file contents without `allowed_classes` restriction. If an attacker can write to cache dir, this enables object injection.
- **Fix:** Use `['allowed_classes' => false]` as second arg, or switch to `json_encode`/`json_decode`.

### 2. ✅ `Sanitizer::path()` bare `..` replacement is overly aggressive
- **File:** `app/Core/Security/Sanitizer.php` (~line 122-125)
- **Issue:** `str_replace(['../', '..'], '', $value)` mangles legitimate strings containing `..` (e.g. `backup..2024.txt`). The bare `..` pattern is too broad.
- **Fix:** Only strip `../` and `..\` traversal sequences, not bare `..`.

### 3. ✅ `Connection::configureConnection()` SQL interpolation
- **File:** `app/Core/Database/Connection.php` (~line 96-107)
- **Issue:** `SET TIME ZONE`, `SET NAMES`, `SET search_path` use string interpolation from config values. If config comes from compromised env vars, this is injectable.
- **Fix:** Validate/whitelist config values before interpolation.

### 4. ✅ `RateLimiter::hit()` resets TTL on every call
- **File:** `app/Core/Security/RateLimiter.php` (~line 49-58)
- **Issue:** Each `hit()` does `get` then `put` with fresh TTL, resetting the decay window. Continuous attacker requests never reach the limit.
- **Fix:** Only set TTL on first hit; subsequent hits should increment without resetting TTL.

---

## 🟡 Medium

### 5. ✅ `Sanitizer::array()` dynamic static method dispatch
- **File:** `app/Core/Security/Sanitizer.php` (~line 162)
- **Issue:** `self::$method($value)` — if `$method` ever comes from user input, this is RCE.
- **Fix:** Validate `$method` against an explicit allowlist of sanitizer methods.

### 6. ✅ `GateForUser::check()` permanently overrides user resolver
- **File:** `app/Core/Authorization/GateForUser.php` (~line 62-65)
- **Issue:** `forUser()` sets the Gate's resolver but never restores the original. Shared Gate instance will use wrong user after call.
- **Fix:** Save and restore the original resolver in a `try/finally`.

### 7. ✅ `bin/console` registers same command class for two aliases
- **File:** `bin/console` (~line 22-23)
- **Issue:** `assets:publish` and `assets:clear` both map to `AssetsPublishCommand`. Likely a bug.
- **Fix:** Create or reference the correct `AssetsClearCommand` class.

### 8. ✅ `RedisStore::flush()` and `RedisSessionHandler` use `KEYS` command
- **File:** `app/Core/Cache/RedisStore.php` (~line 191), plus `RedisSessionHandler`
- **Issue:** `KEYS` is O(N) and blocks Redis. Dangerous in production with large keyspaces.
- **Fix:** Replace with `SCAN`-based iteration.

### 9. ✅ `Model::getTable()` naive pluralization
- **File:** `app/Core/Database/Model.php` (~line 102)
- **Issue:** Appends `'s'` — fails for `Category`, `Person`, etc.
- **Fix:** Add basic inflection rules or require explicit `$table` property.

### 10. ✅ `MetricsCollector` instantiated fresh on every call
- **Files:** `app/Core/Cache/FileStore.php` (~line 56), `app/Core/Database/Connection.php`
- **Issue:** `new MetricsCollector()` on every cache hit/query is wasteful.
- **Fix:** Resolve from container or use singleton pattern.

### 11. ✅ `pub/index.php` bypasses Kernel entirely
- **File:** `pub/index.php`
- **Issue:** Uses `SimpleRouter` procedurally instead of `Kernel` → `Pipeline` → `Router`. Middleware pipeline is unused.
- **Fix:** Refactored to use `Kernel::handle()` with full middleware pipeline (`SecurityHeadersMiddleware`, `StartSession`, `VerifyCsrfToken`). Routes defined in `routes/web.php` via full `Router`. `ModuleRenderer` updated to return strings. `WebExceptionHandler` renders styled error pages.

---

## 🟢 Low / Improvement

### 12. ✅ Security tests use custom TestRunner, not PHPUnit
- **File:** `tests/Security/AuthorizationTest.php`
- **Fix:** Migrated to PHPUnit with proper test class at `tests/Unit/Security/AuthorizationTest.php` (45 tests). Mock classes moved to `tests/Fixtures/`. Old custom TestRunner file removed.

### 13. ✅ `TestCase` base class is empty
- **File:** `tests/TestCase.php`
- **Fix:** Added shared setUp/tearDown with `Application::resetInstance()`, `createApplication()`, and `app()` helpers.

### 14. ✅ No HTTP/Routing/Validation test coverage
- **Fix:** Already extensive — 385+ tests across Request, Response, HeaderBag, ParameterBag, Kernel, Pipeline, Router, Route, UrlGenerator, Validator, and 6+ middleware tests. Fixed pre-existing `RateLimitMiddlewareTest` mock bug.

### 15. ✅ PHPStan baseline reduced from 188 → 140 suppressed errors
- **Fix:** Created `phpstan.neon` config. Fixed all non-strict `in_array()` (22 files), `array_search()`, `base64_decode()`. Replaced ~15 `empty()` calls with strict comparisons. Fixed `safe_log` dynamic method call. Added `@property` annotations to `UsesRedis` trait. Remaining 140 are style-level (`empty()`, `new static()`, short ternary).

---
---

# DRY / Clean Code Audit

Generated from the DRY audit completed on 2025-02-09.
Excludes single-line repetitions per user request.

---

## 🔴 High Impact (Consolidate)

### D1. ✅ `formatTrace()` duplicated identically in `Handler` and `Reporter`
- **Files:** `app/Core/Error/Handler.php` (~line 169-184), `app/Core/Error/Reporter.php` (~line 122-137)
- **Issue:** Both classes contain a byte-for-byte identical `formatTrace(Throwable $e): array` method with the same `sprintf('#%d %s:%d %s%s%s()', ...)` pattern.
- **Fix:** Extract to a shared static method (e.g. `Handler::formatTrace()` and have `Reporter` call it), or create a small `ExceptionFormatter` utility class.

### D2. ✅ `redis()` and `key()` methods duplicated in `RedisStore` and `RedisSessionHandler`
- **Files:** `app/Core/Cache/RedisStore.php` (~line 37-48), `app/Core/Session/RedisSessionHandler.php` (~line 79-90)
- **Issue:** Both classes define identical `redis(): Redis` (calls `$this->redis->connection($this->connection)`) and `key(string): string` (returns `$this->prefix . $key`). Both also share the same constructor parameter pattern (`RedisManager`, `connection`, `prefix`, `ttl`).
- **Fix:** Extract a `Concerns\UsesRedis` trait with `redis()`, `key()`, and the shared constructor properties.

### D3. ✅ Redis `KEYS` + `DEL` pattern repeated 4 times
- **Files:** `RedisStore::flush()` (~line 191), `RedisStore::clearByPattern()` (~line 308), `RedisSessionHandler::getAllSessionIds()` (~line 312), `RedisSessionHandler::destroyAll()` (~line 341)
- **Issue:** All four methods follow the same 5-6 line pattern: `$keys = $this->redis()->keys(pattern); if (!empty($keys)) { $this->redis()->del(...$keys); }` — each wrapped in try/catch with logging.
- **Fix:** Add a `deleteByPattern(string $pattern): int` method to the `UsesRedis` trait (from D2).

### D4. ✅ Redis error-logging boilerplate repeated ~20 times
- **Files:** `RedisStore` (~15 catch blocks), `RedisSessionHandler` (~8 catch blocks)
- **Issue:** Every Redis operation has a catch block doing `logger()->warning/error('Cache/Session X failed', ['key' => $key, 'error' => $e->getMessage()])`. Only the operation name and log level differ.
- **Fix:** Add a `logRedisError(string $operation, RedisException $e, array $context = []): void` method to the `UsesRedis` trait. Each catch becomes a single-line call.

### D5. ✅ `ensureDirectory` reimplemented in 3 places despite existing helper
- **Files:** `app/Core/Log/LogChannel.php` (~line 169-175), `app/Core/Console/Commands/AssetsPublishCommand.php` (~line 253-258), `app/Core/Module/ModuleRegistry.php` (~line 357-359)
- **Issue:** `LogChannel::ensureDirectoryExists()`, `AssetsPublishCommand::ensureDirectory()`, and inline `if (!is_dir()) mkdir()` in `ModuleRegistry::saveToCache()` all reimplement the `ensure_directory()` helper that already exists in `app/Core/Support/helpers.php`.
- **Fix:** Replace all three with calls to `ensure_directory()`.

### D6. ✅ `SetupCommand` duplicates DB config array construction
- **File:** `app/Core/Console/Commands/SetupCommand.php` (~line 518-525 and ~line 539-548)
- **Issue:** `backupDatabase()` and `createDatabaseConnection()` both build the exact same config array from the same env vars (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- **Fix:** Extract a `getDatabaseConfig(): array` private method and call it from both places.

---

## 🟡 Medium Impact

### D7. ✅ `ParameterBag` and `HeaderBag` share ~80% identical interface
- **Files:** `app/Core/Http/ParameterBag.php`, `app/Core/Http/HeaderBag.php`
- **Issue:** Both implement `IteratorAggregate`, `Countable` with nearly identical `all()`, `keys()`, `has()`, `remove()`, `getIterator()`, `count()` methods. The key difference is `HeaderBag` normalizes keys and stores arrays of values.
- **Fix:** Extracted `AbstractBag` with shared `$items` property, `all()`, `keys()`, `getIterator()`, `count()`. Both bags extend it.

### D8. ✅ `Response::getHeaders()` and `Request::headers()` share identical logic
- **Files:** `app/Core/Http/Response.php` (~line 138-146), `app/Core/Http/Request.php` (~line 178-186)
- **Issue:** Both iterate `$this->headers->all()` and extract the first value from each header array into a flat key→value map. Identical 5-line loop.
- **Fix:** Add a `toFlatArray(): array` method to `HeaderBag` and call it from both `Request::headers()` and `Response::getHeaders()`.

### D9. ✅ Console `line()` and `error()` output helpers duplicated
- **Files:** `app/Core/Console/Command.php` (~line 80-93), `app/Core/Console/Application.php` (~line 260-268)
- **Issue:** `Command::line()`, `Command::error()` and `Application::line()`, `Application::error()` are identical implementations.
- **Fix:** Extract a `Concerns\WritesOutput` trait or have `Application` delegate to a shared output helper.

### D10. ✅ `InstallCommand` UI helpers should live in base `Command`
- **File:** `app/Core/Console/Commands/InstallCommand.php` (~line 324-367)
- **Issue:** `askSecret()` and `choice()` are general-purpose interactive input methods defined only in `InstallCommand`. Any future command needing these would have to duplicate them.
- **Fix:** Move `askSecret()` and `choice()` to the base `Command` class alongside the existing `ask()` and `confirm()`.

### D11. ✅ `SimpleRouter` duplicates route registration methods from `RegistersRoutes` trait
- **Files:** `app/Core/Routing/SimpleRouter.php` (~line 54-101), `app/Core/Routing/Concerns/RegistersRoutes.php`
- **Issue:** `SimpleRouter` defines `get()`, `post()`, `put()`, `patch()`, `delete()`, `any()` methods that mirror the same signatures in `RegistersRoutes`. The `SimpleRouter` is a simpler version but the method signatures and patterns are identical.
- **Fix:** Removed `SimpleRouter` entirely. After #11 refactor, `pub/index.php` uses the full `Router` + `Kernel` pipeline, making `SimpleRouter` unused. Deleted `SimpleRouter.php` and `SimpleRouterTest.php`.

### D12. ✅ `ModuleRegistry::saveToCache()` has its own cache-writing code
- **File:** `app/Core/Module/ModuleRegistry.php` (~line 354-373)
- **Issue:** Manually builds `<?php return var_export(...)` string and writes to file. The `save_php_array()` helper in `helpers.php` already does exactly this with proper formatting, locking, and header comments.
- **Fix:** Replace with `save_php_array($this->cachePath, [...], 'Module Registry Cache')`.

### D13. ✅ `bin2hex(random_bytes(N))` repeated in ~8 files
- **Files:** `Application.php`, `Kernel.php`, `LogManager.php`, `RedisQueue.php`, `Csrf.php`, `RedisSessionHandler.php`, `SessionGuard.php`, `PasswordResetService.php`
- **Issue:** Random token generation via `bin2hex(random_bytes(N))` is used with varying lengths (8, 16, 32 bytes). `Str::random()` already exists in the codebase.
- **Fix:** Replace all `bin2hex(random_bytes(N))` calls with `Str::random(N * 2)` (since bin2hex doubles the length). This centralizes the token generation strategy and makes it trivial to change later.

---

## 🟢 Low Impact / Style

### D14. ✅ `function_exists('logger')` guard used in ~10 files
- **Files:** `Connection.php`, `Pipeline.php`, `DispatchesRoutes.php`, `Facade.php`, `ModuleRegistry.php`, `ExceptionHandler.php`, etc.
- **Issue:** Many classes guard logger calls with `if (function_exists('logger'))`. This is defensive but repetitive.
- **Fix:** Added `safe_log(string $level, string $message, array $context)` helper to `helpers.php` that internalizes the guard. Replaced 12 occurrences across 9 files. Only 1 legitimate instance remains (Connection.php query channel needs logger instance directly).

### D15. ✅ Maker commands share boilerplate pattern
- **Files:** `MakeMigrationCommand.php`, `MakeSeederCommand.php`, `ModuleMakeCommand.php`
- **Issue:** All three follow the same flow: validate args → check if exists → ensure directory → write template → print success. The arg validation + error messaging pattern is repeated.
- **Fix:** Could extract a `Concerns\GeneratesFiles` trait with `ensureArgument()`, `ensureNotExists()`, `writeTemplate()` helpers. *Low priority — commands are infrequently changed.*

### D16. ✅ `memory_get_peak_usage(true) / 1024 / 1024` repeated
- **Files:** `Kernel.php` (~line 189, 201), `HealthCheck.php` (~line 367-370)
- **Issue:** Memory formatting calculation repeated in multiple places.
- **Fix:** Marginal — only 2-3 occurrences. Could add `Str::formatBytes()` but not worth it for this alone.
