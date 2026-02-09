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
namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Database\Connection;
use Throwable;

/**
 * Health Check Command
 *
 * Validates system health: env, database, directories, config.
 */
class HealthCheckCommand extends Command
{
    protected string $name = 'health:check';
    protected string $description = 'Validate system health (env, db, dirs, config)';
    protected array $aliases = ['doctor', 'sys:check'];

    protected int $errors = 0;
    protected int $warnings = 0;

    public function handle(array $args = []): int
    {
        $verbose = in_array('--verbose', $args, true) || in_array('-v', $args, true);

        $this->line("System Health Check");
        $this->line(str_repeat('═', 50));

        // 1. Environment
        $this->checkEnvironment($verbose);

        // 2. Required directories
        $this->checkDirectories($verbose);

        // 3. Database connectivity
        $this->checkDatabase($verbose);

        // 4. Config validity
        $this->checkConfig($verbose);

        // 5. Module integrity
        $this->checkModules($verbose);

        // Summary
        $this->line(str_repeat('═', 50));

        if ($this->errors > 0) {
            $this->error("Health check failed: {$this->errors} error(s), {$this->warnings} warning(s)");

            return 1;
        }

        if ($this->warnings > 0) {
            $this->warn("Health check passed with {$this->warnings} warning(s)");

            return 0;
        }

        $this->info("✅ All checks passed!");

        return 0;
    }

    protected function checkEnvironment(bool $verbose): void
    {
        $this->line("\n📋 Environment");

        $rootDir = $this->getRootDir();
        $envFile = $rootDir . '/.env';

        // Check .env exists
        if (! file_exists($envFile)) {
            $this->addError(".env file not found");
            $this->line("  Run: php bin/console s:i");

            return;
        }
        $this->pass(".env file exists");

        // Load and check required keys
        $env = $this->loadEnv($envFile);
        $required = ['APP_ENV', 'APP_KEY', 'SITE_URL'];

        foreach ($required as $key) {
            if (empty($env[$key])) {
                $this->addError("Missing required env: {$key}");
            } elseif ($verbose) {
                $this->pass("{$key} is set");
            }
        }

        // Warn if debug in production
        $appEnv = $env['APP_ENV'] ?? 'production';
        $appDebug = ($env['APP_DEBUG'] ?? 'false') === 'true';

        if ($appEnv === 'production' && $appDebug) {
            $this->addWarning("APP_DEBUG=true in production environment");
        }

        // Check APP_KEY length
        $appKey = $env['APP_KEY'] ?? '';
        if (strlen($appKey) < 32) {
            $this->addWarning("APP_KEY should be at least 32 characters");
        }
    }

    protected function checkDirectories(bool $verbose): void
    {
        $this->line("\n📁 Directories");

        $rootDir = $this->getRootDir();
        $dirs = [
            'var/cache' => true,   // must be writable
            'var/log' => true,
            'var/state' => true,
            'pub' => false,        // just needs to exist
        ];

        foreach ($dirs as $dir => $writable) {
            $path = $rootDir . '/' . $dir;

            if (! is_dir($path)) {
                $this->addError("Directory missing: {$dir}");
                continue;
            }

            if ($writable && ! is_writable($path)) {
                $this->addError("Directory not writable: {$dir}");
                continue;
            }

            if ($verbose) {
                $this->pass("{$dir} " . ($writable ? '(writable)' : ''));
            }
        }

        if (! $verbose && $this->errors === 0) {
            $this->pass("All directories OK");
        }
    }

    protected function checkDatabase(bool $verbose): void
    {
        $this->line("\n🗄️  Database");

        $rootDir = $this->getRootDir();
        $env = $this->loadEnv($rootDir . '/.env');

        // Check if DB is configured
        $dbHost = $env['DB_HOST'] ?? '';
        $dbName = $env['DB_DATABASE'] ?? '';

        if (empty($dbHost) || empty($dbName)) {
            $this->addWarning("Database not configured (skipping connectivity test)");

            return;
        }

        // Try to connect
        try {
            $config = [
                'driver' => $env['DB_CONNECTION'] ?? 'pgsql',
                'host' => $dbHost,
                'port' => (int) ($env['DB_PORT'] ?? 5432),
                'database' => $dbName,
                'username' => $env['DB_USERNAME'] ?? '',
                'password' => $env['DB_PASSWORD'] ?? '',
            ];

            $connection = new Connection($config);
            $pdo = $connection->getPdo();

            // Simple connectivity test
            $pdo->query('SELECT 1');

            $this->pass("Database connection OK ({$config['driver']}://{$dbHost}/{$dbName})");

            // Check migrations table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM migrations");
                $count = $stmt->fetchColumn();
                if ($verbose) {
                    $this->line("    • {$count} migration(s) recorded");
                }
            } catch (Throwable) {
                $this->addWarning("Migrations table not found (run s:up)");
            }

        } catch (Throwable $e) {
            $this->addError("Database connection failed: " . $e->getMessage());
        }
    }

    protected function checkConfig(bool $verbose): void
    {
        $this->line("\n⚙️  Configuration");

        $rootDir = $this->getRootDir();
        $cacheDir = $rootDir . '/var/cache';

        $cacheFiles = [
            'modules.php' => 'Module registry',
            'config.php' => 'Compiled config',
            'events.php' => 'Event listeners',
            'container.php' => 'DI container',
            'routes.php' => 'Routes',
            'middleware.php' => 'Middleware',
        ];

        $missing = [];
        foreach ($cacheFiles as $file => $desc) {
            $path = $cacheDir . '/' . $file;
            if (! file_exists($path)) {
                $missing[] = $desc;
            } elseif ($verbose) {
                $this->pass("{$desc} cached");
            }
        }

        if (! empty($missing)) {
            $this->addWarning("Compiled caches missing: " . implode(', ', $missing));
            $this->line("    Run: php bin/console s:up");
        } elseif (! $verbose) {
            $this->pass("All compiled caches present");
        }
    }

    protected function checkModules(bool $verbose): void
    {
        $this->line("\n📦 Modules");

        $rootDir = $this->getRootDir();
        $modulesCache = $rootDir . '/var/cache/modules.php';

        if (! file_exists($modulesCache)) {
            $this->addWarning("Module registry not cached");

            return;
        }

        $data = require $modulesCache;
        $modules = $data['modules'] ?? [];
        $loadOrder = $data['loadOrder'] ?? [];

        $enabled = 0;
        $disabled = 0;

        foreach ($modules as $module) {
            if ($module['enabled'] ?? false) {
                $enabled++;
            } else {
                $disabled++;
            }
        }

        $this->pass("{$enabled} module(s) enabled, {$disabled} disabled");

        if ($verbose && ! empty($loadOrder)) {
            $this->line("    Load order: " . implode(' → ', $loadOrder));
        }

        // Check for missing module files
        foreach ($modules as $name => $module) {
            $path = $module['path'] ?? '';
            if (! empty($path) && ! is_dir($path)) {
                $this->addError("Module directory missing: {$name}");
            }
        }
    }

    protected function loadEnv(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $env = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        return $env;
    }

    protected function pass(string $message): void
    {
        $this->line("  \033[32m✓\033[0m {$message}");
    }

    protected function addError(string $message): void
    {
        $this->errors++;
        $this->line("  \033[31m✗\033[0m {$message}");
    }

    protected function addWarning(string $message): void
    {
        $this->warnings++;
        $this->line("  \033[33m⚠\033[0m {$message}");
    }
}
