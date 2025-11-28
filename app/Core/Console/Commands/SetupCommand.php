<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Compiler\CompilerManager;
use App\Core\Database\Migrator;
use App\Core\Database\Connection;
use App\Core\Database\MigrationState;
use App\Core\Database\DatabaseBackup;
use App\Core\Module\ModuleRegistry;
use App\Core\Module\ModuleHookRunner;
use App\Core\Support\EnvManager;

/**
 * Setup Command (Core)
 * 
 * Handles framework-level setup and updates:
 * - Module registry
 * - APP_VERSION cache busting
 * - Database migrations
 * - Cache clearing
 * - Permissions
 * 
 * Flags:
 *   --skip-db       Skip database migrations
 *   --skip-compile  Skip compilation step
 *   --skip-cache    Skip cache clearing
 *   --skip-hooks    Skip module hooks
 *   --no-backup     Skip database backup before migrations
 *   --dry-run       Show what would be done without executing
 */
class SetupCommand extends Command
{
    protected string $name = 'setup:update';
    protected string $description = 'Run migrations, clear cache, fix permissions';
    protected array $aliases = ['s:up'];

    protected string $rootDir;
    protected EnvManager $env;

    // Flags
    protected bool $skipDb = false;
    protected bool $skipCompile = false;
    protected bool $skipCache = false;
    protected bool $skipHooks = false;
    protected bool $noBackup = false;
    protected bool $dryRun = false;
    protected ?string $targetEnv = null;

    public function __construct()
    {
        $this->rootDir = $this->getRootDir();
        $this->env = new EnvManager($this->rootDir . '/.env');
    }

    public function handle(array $args = []): int
    {
        // Parse flags
        $this->parseFlags($args);

        if ($this->dryRun) {
            echo "🔍 DRY RUN - No changes will be made\n";
        }
        
        echo "🚀 Running setup...\n";
        echo str_repeat('─', 50) . "\n";

        // Step 1: Check environment
        $this->checkEnvironment();

        // Step 2: Rebuild module registry
        $this->rebuildModuleRegistry();

        // Step 3: Run module hooks (install/upgrade/beforeSetup)
        if (!$this->skipHooks) {
            $this->runModuleHooks();
        } else {
            echo "\n🪝 Module Hooks\n  ⏭️  Skipped (--skip-hooks)\n";
        }

        // Step 4: Clear runtime caches (before compilation)
        if (!$this->skipCache) {
            if ($this->dryRun) {
                echo "\n🧹 Caches\n  → Would clear file cache\n";
            } else {
                $this->clearCaches();
            }
        } else {
            echo "\n🧹 Caches\n  ⏭️  Skipped (--skip-cache)\n";
        }

        // Step 5: Compile configs, events, container
        if (!$this->skipCompile) {
            if ($this->dryRun) {
                echo "\n⚙️  Compilation\n  → Would compile: config, events, container, routes, middleware\n";
            } else {
                $this->runCompilation();
            }
        } else {
            echo "\n⚙️  Compilation\n  ⏭️  Skipped (--skip-compile)\n";
        }

        // Step 6: Update APP_VERSION
        if (!$this->dryRun) {
            $this->updateAppVersion();
        } else {
            echo "\n🔄 Cache Busting\n  → Would update APP_VERSION\n";
        }

        // Step 7: Run migrations (if any pending)
        if (!$this->skipDb) {
            $this->runMigrations();
        } else {
            echo "\n📦 Migrations\n  ⏭️  Skipped (--skip-db)\n";
        }

        // Step 8: Fix permissions
        $this->fixPermissions();

        // Step 9: Run module afterSetup hooks
        if (!$this->skipHooks) {
            $this->runModuleAfterSetupHooks();
        }

        // Hook for extending classes
        $this->afterSetup();

        echo str_repeat('─', 50) . "\n";
        
        if ($this->dryRun) {
            echo "🔍 DRY RUN complete - no changes made\n\n";
        } else {
            echo "✅ Setup completed!\n";
            $this->printSummary();
        }

        return 0;
    }

    protected function printSummary(): void
    {
        // Reload env to get updated values
        $this->env->reload();
        
        $appEnv = $this->targetEnv ?? $this->env->get('APP_ENV', 'production');
        $appVersion = $this->env->get('APP_VERSION', 'unknown');
        $gitHash = $this->getGitHash();

        echo "\n📊 Summary\n";
        echo "  • Environment: {$appEnv}\n";
        echo "  • Version: {$appVersion}\n";
        
        if ($gitHash) {
            echo "  • Git: {$gitHash}\n";
        }
        
        echo "\n";
    }

    protected function parseFlags(array $args): void
    {
        $this->skipDb = in_array('--skip-db', $args);
        $this->skipCompile = in_array('--skip-compile', $args);
        $this->skipCache = in_array('--skip-cache', $args);
        $this->skipHooks = in_array('--skip-hooks', $args);
        $this->noBackup = in_array('--no-backup', $args);
        $this->dryRun = in_array('--dry-run', $args);

        // Parse --env=value
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--env=')) {
                $this->targetEnv = substr($arg, 6);
            }
        }
    }

    protected function checkEnvironment(): void
    {
        echo "\n📋 Environment\n";

        $appEnv = $this->targetEnv ?? $this->env->get('APP_ENV', 'production');
        $phpVersion = PHP_VERSION;
        $gitHash = $this->getGitHash();

        echo "  • PHP: {$phpVersion}\n";
        echo "  • Environment: {$appEnv}\n";
        
        if ($gitHash) {
            echo "  • Git: {$gitHash}\n";
        }

        // Environment-specific warnings
        if ($appEnv === 'production') {
            $appDebug = $this->env->get('APP_DEBUG', 'false') === 'true';
            if ($appDebug) {
                echo "  ⚠️  APP_DEBUG=true in production (disable for security)\n";
            }
        }

        if (version_compare($phpVersion, '8.1.0', '<')) {
            echo "  ⚠️  PHP 8.1+ required\n";
        }

        if (!$this->env->exists()) {
            echo "  ⚠️  .env file not found - run: php bin/console s:i\n";
        }

        // If --env was specified, update .env file
        if ($this->targetEnv !== null && !$this->dryRun) {
            $this->env->persist('APP_ENV', $this->targetEnv);
        }
    }

    protected function getGitHash(): ?string
    {
        $gitDir = $this->rootDir . '/.git';
        if (!is_dir($gitDir)) {
            return null;
        }

        $output = [];
        $result = null;
        exec('git rev-parse --short HEAD 2>/dev/null', $output, $result);

        if ($result === 0 && !empty($output[0])) {
            return $output[0];
        }

        return null;
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
            echo "  ✓ Routes compiled\n";
            echo "  ✓ Middleware compiled\n";
            
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

        if (!$this->env->exists()) {
            echo "  ⚠️  Skipped (no .env file)\n";
            return;
        }

        $newVersion = (string) time();
        $this->env->persist('APP_VERSION', $newVersion);
        echo "  ✓ APP_VERSION updated to {$newVersion}\n";
    }

    protected function runMigrations(): void
    {
        echo "\n📦 Migrations\n";

        $migrationsPath = $this->rootDir . '/database/migrations';
        $migrationState = new MigrationState();

        // Check for previous failed migration
        if (!$migrationState->isSafe()) {
            $failed = $migrationState->getFailedInfo();
            echo "  ⛔ System in UNSAFE state - previous migration failed\n";
            echo "  • Failed: {$failed['migration']}\n";
            echo "  • Error: {$failed['error']}\n";
            echo "  ℹ️  Fix the issue manually, then run:\n";
            echo "      php bin/console migrate:reset-state\n";
            return;
        }

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
        $dbHost = $this->env->get('DB_HOST');
        $dbName = $this->env->get('DB_DATABASE');

        if (empty($dbHost) || empty($dbName)) {
            echo "  • Found " . count($migrations) . " migration file(s)\n";
            echo "  ℹ️  Database not configured - skipping\n";
            return;
        }

        // Try to run migrations
        try {
            $connection = $this->createDatabaseConnection();
            $migrator = new Migrator($connection, $migrationsPath);
            
            $pending = $migrator->status();
            $pendingMigrations = array_filter($pending, fn($m) => !$m['ran']);
            $pendingCount = count($pendingMigrations);
            
            if ($pendingCount === 0) {
                echo "  ✓ No pending migrations\n";
                return;
            }

            echo "  • Found {$pendingCount} pending migration(s)\n";

            // Dry run - just show what would run
            if ($this->dryRun) {
                foreach ($pendingMigrations as $migration) {
                    echo "  → Would run: {$migration['migration']}\n";
                }
                return;
            }

            // Backup before migrations (unless --no-backup)
            if (!$this->noBackup) {
                $this->backupDatabase();
            }

            // Run migrations with state tracking
            foreach ($pendingMigrations as $migration) {
                $name = $migration['migration'];
                $migrationState->markStarted($name);
                
                try {
                    $migrator->runMigration($name);
                    $migrationState->markCompleted($name);
                    echo "  ✓ Migrated: {$name}\n";
                } catch (\Throwable $e) {
                    $migrationState->markFailed($name, $e->getMessage());
                    throw $e;
                }
            }
            
            echo "  • Ran {$pendingCount} migration(s)\n";
            
        } catch (\Throwable $e) {
            echo "  ⚠️  Migration failed: " . $e->getMessage() . "\n";
            echo "  ⛔ System marked as UNSAFE\n";
            echo "  ℹ️  Fix the issue, then run: php bin/console migrate:reset-state\n";
            if (!$this->noBackup) {
                echo "  ℹ️  Restore from var/backups/ if needed\n";
            }
        }
    }

    protected function backupDatabase(): void
    {
        $config = [
            'driver' => $this->env->get('DB_CONNECTION', 'pgsql'),
            'host' => $this->env->get('DB_HOST', '127.0.0.1'),
            'port' => $this->env->get('DB_PORT', '5432'),
            'database' => $this->env->get('DB_DATABASE'),
            'username' => $this->env->get('DB_USERNAME'),
            'password' => $this->env->get('DB_PASSWORD'),
        ];

        $backup = new DatabaseBackup($config);
        $result = $backup->backup();

        if ($result['success']) {
            $size = DatabaseBackup::formatBytes($result['size']);
            echo "  ✓ Database backed up ({$size})\n";
        } else {
            echo "  ⚠️  {$result['error']}\n";
        }
    }

    protected function createDatabaseConnection(): Connection
    {
        $config = [
            'driver' => $this->env->get('DB_CONNECTION', 'pgsql'),
            'host' => $this->env->get('DB_HOST', '127.0.0.1'),
            'port' => (int) $this->env->get('DB_PORT', '5432'),
            'database' => $this->env->get('DB_DATABASE'),
            'username' => $this->env->get('DB_USERNAME'),
            'password' => $this->env->get('DB_PASSWORD'),
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
            clear_directory($cacheDir, true);
            echo "  ✓ File cache cleared\n";
        }

        // Clear rate limits
        $rateLimitsDir = $this->rootDir . '/var/cache/rate_limits';
        if (is_dir($rateLimitsDir)) {
            clear_directory($rateLimitsDir, true);
            echo "  ✓ Rate limits cleared\n";
        }
    }

    protected function fixPermissions(): void
    {
        echo "\n🔒 Permissions\n";

        $permsCmd = new PermissionsCommand();
        
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
}
