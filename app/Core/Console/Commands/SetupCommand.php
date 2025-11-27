<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Compiler\CompilerManager;
use App\Core\Database\Migrator;
use App\Core\Database\Connection;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleHookRunner;

/**
 * Setup Command (Core)
 * 
 * Handles framework-level setup and updates:
 * - Module registry
 * - APP_VERSION cache busting
 * - Database migrations
 * - Cache clearing
 * - Permissions
 */
class SetupCommand extends Command
{
    protected string $name = 'setup:update';
    protected string $description = 'Run migrations, clear cache, fix permissions';
    protected array $aliases = ['s:up'];

    protected string $rootDir;
    protected string $envFile;
    protected array $envVars = [];

    public function __construct()
    {
        $this->rootDir = $this->getRootDir();
        $this->envFile = $this->rootDir . '/.env';
        $this->loadEnvFile();
    }

    public function handle(array $args = []): int
    {
        echo "🚀 Running setup...\n";
        echo str_repeat('─', 50) . "\n";

        // Step 1: Check environment
        $this->checkEnvironment();

        // Step 2: Rebuild module registry
        $this->rebuildModuleRegistry();

        // Step 3: Run module hooks (install/upgrade/beforeSetup)
        $this->runModuleHooks();

        // Step 4: Clear runtime caches (before compilation)
        $this->clearCaches();

        // Step 5: Compile configs, events, container
        $this->runCompilation();

        // Step 6: Update APP_VERSION
        $this->updateAppVersion();

        // Step 7: Run migrations (if any pending)
        $this->runMigrations();

        // Step 8: Fix permissions
        $this->fixPermissions();

        // Step 9: Run module afterSetup hooks
        $this->runModuleAfterSetupHooks();

        // Hook for extending classes
        $this->afterSetup();

        echo str_repeat('─', 50) . "\n";
        echo "✅ Setup completed!\n\n";

        return 0;
    }

    protected function checkEnvironment(): void
    {
        echo "\n📋 Environment\n";

        $appEnv = $this->getEnv('APP_ENV', 'production');
        $phpVersion = PHP_VERSION;

        echo "  • PHP: {$phpVersion}\n";
        echo "  • Environment: {$appEnv}\n";

        if (version_compare($phpVersion, '8.1.0', '<')) {
            echo "  ⚠️  PHP 8.1+ required\n";
        }

        if (!file_exists($this->envFile)) {
            echo "  ⚠️  .env file not found - run: php bin/console s:i\n";
        }
    }

    protected ModuleRegistry $registry;
    protected ModuleHookRunner $hookRunner;

    protected function rebuildModuleRegistry(): void
    {
        echo "\n📦 Modules\n";

        try {
            $this->registry = new ModuleRegistry();
            $this->registry->rebuild();

            $modules = $this->registry->all();
            $enabled = array_filter($modules, fn($m) => $m->enabled);

            echo "  ✓ Registry rebuilt\n";
            echo "  • Found " . count($modules) . " module(s)\n";
            echo "  • Enabled: " . count($enabled) . "\n";
            echo "  • Load order: " . implode(' → ', $this->registry->getLoadOrder()) . "\n";

            $this->hookRunner = new ModuleHookRunner($this->registry);
        } catch (\Throwable $e) {
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
        }
    }

    protected function runModuleHooks(): void
    {
        if (!isset($this->hookRunner)) {
            return;
        }

        echo "\n🪝 Module Hooks\n";

        try {
            $results = $this->hookRunner->runSetupHooks();

            if (!empty($results['installed'])) {
                foreach ($results['installed'] as $name) {
                    echo "  ✓ Installed: {$name}\n";
                }
            }

            if (!empty($results['upgraded'])) {
                foreach ($results['upgraded'] as $info) {
                    echo "  ✓ Upgraded: {$info}\n";
                }
            }

            $hookCount = count($results['beforeSetup']);
            if ($hookCount > 0) {
                echo "  • beforeSetup: {$hookCount} module(s)\n";
            }

            if (empty($results['installed']) && empty($results['upgraded']) && $hookCount === 0) {
                echo "  • No hooks to run\n";
            }
        } catch (\Throwable $e) {
            echo "  ⚠️  Hook error: " . $e->getMessage() . "\n";
        }
    }

    protected function runModuleAfterSetupHooks(): void
    {
        if (!isset($this->hookRunner)) {
            return;
        }

        try {
            $results = $this->hookRunner->runAfterSetupHooks();
            // Silent unless there's an error
        } catch (\Throwable $e) {
            echo "\n⚠️  afterSetup hook error: " . $e->getMessage() . "\n";
        }
    }

    protected function runCompilation(): void
    {
        echo "\n⚙️  Compilation\n";

        try {
            $compiler = new CompilerManager($this->rootDir);
            $compiler->compileAll();

            $stats = $compiler->getStats();
            
            echo "  ✓ Config compiled\n";
            echo "  ✓ Events compiled\n";
            echo "  ✓ Container compiled\n";
            
            if ($stats['container']['total_providers'] > 0) {
                echo "  • Providers: " . $stats['container']['total_providers'] . "\n";
            }
            if ($stats['container']['deferred_providers'] > 0) {
                echo "  • Deferred: " . $stats['container']['deferred_providers'] . "\n";
            }
            
            $eventCount = array_sum($stats['events']);
            if ($eventCount > 0) {
                echo "  • Event listeners: " . $eventCount . "\n";
            }
        } catch (\Throwable $e) {
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
        }
    }

    protected function updateAppVersion(): void
    {
        echo "\n🔄 Cache Busting\n";

        if (!file_exists($this->envFile)) {
            echo "  ⚠️  Skipped (no .env file)\n";
            return;
        }

        $newVersion = time();
        $content = file_get_contents($this->envFile);

        if (preg_match('/^APP_VERSION=.*/m', $content)) {
            $content = preg_replace('/^APP_VERSION=.*/m', 'APP_VERSION=' . $newVersion, $content);
        } else {
            $content .= "\nAPP_VERSION=" . $newVersion . "\n";
        }

        file_put_contents($this->envFile, $content);
        echo "  ✓ APP_VERSION updated to {$newVersion}\n";
    }

    protected function runMigrations(): void
    {
        echo "\n📦 Migrations\n";

        $migrationsPath = $this->rootDir . '/database/migrations';

        if (!is_dir($migrationsPath)) {
            echo "  • No migrations directory\n";
            return;
        }

        $migrations = glob($migrationsPath . '/*.php');

        if (empty($migrations)) {
            echo "  • No pending migrations\n";
            return;
        }

        // Check if database is configured
        $dbHost = $this->getEnv('DB_HOST');
        $dbName = $this->getEnv('DB_DATABASE');

        if (empty($dbHost) || empty($dbName)) {
            echo "  • Found " . count($migrations) . " migration file(s)\n";
            echo "  ℹ️  Database not configured - skipping\n";
            return;
        }

        // Try to run migrations
        try {
            $connection = $this->createDatabaseConnection();
            $migrator = new Migrator($connection, $migrationsPath);
            
            $pending = count($migrator->status());
            $ran = $migrator->migrate();
            
            if (empty($ran)) {
                echo "  ✓ No pending migrations\n";
            } else {
                foreach ($ran as $migration) {
                    echo "  ✓ Migrated: {$migration}\n";
                }
                echo "  • Ran " . count($ran) . " migration(s)\n";
            }
        } catch (\Throwable $e) {
            echo "  ⚠️  Migration failed: " . $e->getMessage() . "\n";
            echo "  ℹ️  Run migrations manually: php bin/console migrate\n";
        }
    }

    protected function createDatabaseConnection(): Connection
    {
        $config = [
            'driver' => $this->getEnv('DB_CONNECTION', 'pgsql'),
            'host' => $this->getEnv('DB_HOST', '127.0.0.1'),
            'port' => (int) $this->getEnv('DB_PORT', '5432'),
            'database' => $this->getEnv('DB_DATABASE'),
            'username' => $this->getEnv('DB_USERNAME'),
            'password' => $this->getEnv('DB_PASSWORD'),
        ];

        return new Connection($config);
    }

    protected function clearCaches(): void
    {
        echo "\n🧹 Caches\n";

        // Clear OPcache
        if (function_exists('opcache_reset')) {
            if (@opcache_reset()) {
                echo "  ✓ OPcache cleared\n";
            }
        }

        // Clear file cache
        $cacheDir = $this->rootDir . '/var/cache';
        if (is_dir($cacheDir)) {
            $this->clearDirectory($cacheDir, true);
            echo "  ✓ File cache cleared\n";
        }

        // Clear rate limits
        $rateLimitsDir = $this->rootDir . '/var/cache/rate_limits';
        if (is_dir($rateLimitsDir)) {
            $this->clearDirectory($rateLimitsDir, true);
            echo "  ✓ Rate limits cleared\n";
        }
    }

    protected function fixPermissions(): void
    {
        echo "\n🔒 Permissions\n";

        $permsCmd = new PermissionsCommand();
        
        // Capture and suppress default output
        ob_start();
        $permsCmd->execute('setup:permissions', []);
        ob_end_clean();

        echo "  ✓ Permissions fixed\n";
    }

    /**
     * Hook for extending classes to add custom setup steps
     */
    protected function afterSetup(): void
    {
        // Override in child classes
    }

    protected function loadEnvFile(): void
    {
        if (!file_exists($this->envFile)) {
            return;
        }

        $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $this->envVars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }

    protected function getEnv(string $key, string $default = ''): string
    {
        return $this->envVars[$key] ?? $default;
    }

    protected function getRootDir(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath();
            } catch (\Throwable) {}
        }
        return dirname(__DIR__, 4);
    }

    protected function clearDirectory(string $dir, bool $preserve = false): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \FilesystemIterator($dir);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->clearDirectory($item->getPathname());
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        if (!$preserve) {
            @rmdir($dir);
        }
    }
}
