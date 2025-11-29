<?php

declare(strict_types=1);


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
use App\Core\Cache\CacheManager;
use App\Core\Compiler\CompilerManager;

/**
 * Cache Clear Command
 * 
 * Clears cache pools and compiled files.
 */
class CacheClearCommand extends Command
{
    protected string $name = 'cache:clear';
    protected string $description = 'Clear cache pools and compiled files';
    protected array $aliases = ['cc'];

    protected string $rootDir;

    public function __construct()
    {
        $this->rootDir = $this->getRootDir();
    }

    public function handle(array $args = []): int
    {
        // Parse --pool argument
        $pool = null;
        $all = false;
        $compiled = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--pool=')) {
                $pool = substr($arg, 7);
            } elseif ($arg === '--all' || $arg === '-a') {
                $all = true;
            } elseif ($arg === '--compiled' || $arg === '-c') {
                $compiled = true;
            }
        }

        $this->line("🧹 Clearing caches...");
        $this->line(str_repeat('─', 40));

        // Clear specific pool
        if ($pool !== null) {
            $this->clearPool($pool);
            return 0;
        }

        // Clear compiled files
        if ($compiled) {
            $this->clearCompiled();
            return 0;
        }

        // Clear all (default behavior)
        $this->clearRuntimeCaches();

        if ($all) {
            $this->clearCompiled();
        }

        $this->line(str_repeat('─', 40));
        $this->info("✅ Cache cleared!");

        return 0;
    }

    protected function clearPool(string $pool): void
    {
        $cacheManager = new CacheManager([], $this->rootDir);
        $pools = $cacheManager->getPoolNames();

        if (!in_array($pool, $pools)) {
            $this->error("Unknown pool: {$pool}");
            $this->line("Available pools: " . implode(', ', $pools));
            return;
        }

        $cacheManager->clearPool($pool);
        $this->info("✓ Pool '{$pool}' cleared");
    }

    protected function clearRuntimeCaches(): void
    {
        $this->line("\n📦 Runtime Caches");

        $cacheManager = new CacheManager([], $this->rootDir);

        foreach ($cacheManager->getPoolNames() as $pool) {
            $poolPath = $this->rootDir . '/var/cache/' . $pool;
            if (is_dir($poolPath)) {
                $cacheManager->clearPool($pool);
                $this->line("  ✓ {$pool}");
            }
        }

        // Clear OPcache
        if (function_exists('opcache_reset') && @opcache_reset()) {
            $this->line("  ✓ opcache");
        }
    }

    protected function clearCompiled(): void
    {
        $this->line("\n⚙️  Compiled Files");

        $compiler = new CompilerManager($this->rootDir);
        $compiler->clearAll();

        // Also clear modules cache
        $modulesCache = $this->rootDir . '/var/cache/modules.php';
        if (file_exists($modulesCache)) {
            unlink($modulesCache);
        }

        $this->line("  ✓ config.php");
        $this->line("  ✓ events.php");
        $this->line("  ✓ container.php");
        $this->line("  ✓ modules.php");
    }
}
