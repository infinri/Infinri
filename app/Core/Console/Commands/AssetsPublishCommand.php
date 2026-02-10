<?php declare(strict_types=1);
/**
 * Assets Publish Command
 *
 * Publishes static assets from source directories to pub/assets/.
 *
 * Sources (in order of cascade):
 *   1. Core: app/Core/View/view/{base,frontend,admin}/web/
 *   2. Theme: app/Modules/Theme/view/frontend/web/
 *   3. Modules: app/Modules/{Module}/view/frontend/web/
 *
 * Usage:
 *   php bin/console assets:publish   - Publish all assets
 *   php bin/console assets:clear     - Clear published assets
 *
 * @package App\Core\Console\Commands
 */
namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AssetsPublishCommand extends Command
{
    protected string $name = 'assets:publish';
    protected string $description = 'Publish static assets to pub/assets/';
    protected array $aliases = ['a:pub'];

    public function handle(array $args = []): int
    {
        return $this->publishAssets();
    }

    /**
     * Publish all assets
     */
    protected function publishAssets(): int
    {
        $this->line("📦 Publishing Assets");
        $this->line(str_repeat('─', 50) . "\n");

        $pubAssetsDir = public_path('assets');

        // Ensure pub/assets directory exists
        $this->ensureDirectory($pubAssetsDir);

        // Clear existing (except dist/)
        $this->clearAssets(preserveDist: true);

        // 1. Publish Core assets
        $this->publishCoreAssets();

        // 2. Publish Theme module assets (if exists)
        $this->publishThemeAssets();

        // 3. Publish other module assets
        $this->publishModuleAssets();

        $this->line("\n" . str_repeat('─', 50));
        $this->info("✅ Assets published successfully!\n");

        return 0;
    }

    /**
     * Publish Core view assets
     */
    protected function publishCoreAssets(): void
    {
        $this->line("📁 Core Assets");

        $coreViewDir = app_path('Core/View/view');
        $pubAssetsDir = public_path('assets');
        $areas = ['base', 'frontend', 'admin'];

        foreach ($areas as $area) {
            $sourceDir = "{$coreViewDir}/{$area}/web";
            $destDir = "{$pubAssetsDir}/core/{$area}";

            if (is_dir($sourceDir)) {
                $this->copyDirectory($sourceDir, $destDir);
                $this->line("   ✓ core/{$area}/");
            }
        }
    }

    /**
     * Publish Theme module assets
     */
    protected function publishThemeAssets(): void
    {
        $themeViewDir = app_path('Modules/Theme/view');
        $pubAssetsDir = public_path('assets');

        if (! is_dir($themeViewDir)) {
            return;
        }

        $this->line("\n🎨 Theme Assets");

        foreach (['frontend', 'admin'] as $area) {
            $sourceDir = "{$themeViewDir}/{$area}/web";
            $destDir = "{$pubAssetsDir}/theme/{$area}";

            if (is_dir($sourceDir)) {
                $this->copyDirectory($sourceDir, $destDir);
                $this->line("   ✓ theme/{$area}/");
            }
        }
    }

    /**
     * Publish module assets (excluding Theme)
     */
    protected function publishModuleAssets(): void
    {
        $modulesDir = app_path('Modules');
        $pubAssetsDir = public_path('assets');

        if (! is_dir($modulesDir)) {
            return;
        }

        $modules = array_filter(
            scandir($modulesDir),
            fn ($item) => $item !== '.'
                && $item !== '..'
                && $item !== 'Theme'  // Theme handled separately
                && is_dir("{$modulesDir}/{$item}")
        );

        if (empty($modules)) {
            return;
        }

        $this->line("\n📦 Module Assets");

        foreach ($modules as $module) {
            $moduleViewDir = "{$modulesDir}/{$module}/view";

            if (! is_dir($moduleViewDir)) {
                continue;
            }

            foreach (['frontend', 'admin'] as $area) {
                $sourceDir = "{$moduleViewDir}/{$area}/web";
                $destDir = "{$pubAssetsDir}/modules/{$module}/{$area}";

                if (is_dir($sourceDir)) {
                    $this->copyDirectory($sourceDir, $destDir);
                    $this->line("   ✓ modules/{$module}/{$area}/");
                }
            }
        }
    }

    /**
     * Clear published assets
     */
    protected function clearAssets(bool $preserveDist = false): int
    {
        $pubAssetsDir = public_path('assets');

        if (! $preserveDist) {
            $this->line("🧹 Clearing Assets");
            $this->line(str_repeat('─', 50) . "\n");
        }

        $dirsToClean = ['core', 'theme', 'modules'];

        // Also clean legacy dirs if they exist
        $legacyDirs = ['base', 'frontend'];

        foreach (array_merge($dirsToClean, $legacyDirs) as $dir) {
            $path = "{$pubAssetsDir}/{$dir}";
            if (is_dir($path)) {
                $this->removeDirectory($path);
                if (! $preserveDist) {
                    $this->line("   ✓ Removed {$dir}/");
                }
            }
        }

        if (! $preserveDist) {
            $this->info("\n✅ Assets cleared!");
        }

        return 0;
    }

    /**
     * Copy directory recursively
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($source)) {
            return;
        }

        $this->ensureDirectory($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            if ($item->isDir()) {
                $this->ensureDirectory($destPath);
            } else {
                copy($item->getRealPath(), $destPath);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        @rmdir($dir);
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectory(string $dir): void
    {
        ensure_directory($dir);
    }
}
