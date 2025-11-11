<?php
declare(strict_types=1);
/**
 * Setup Command
 *
 * Handles project setup and update operations
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class SetupCommand
{
    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'setup:update':
                $this->setupUpdate();
                break;
            default:
                echo "Unknown setup command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function setupUpdate(): void
    {
        echo "🚀 Running project setup..." . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;

        // Step 1: Check environment
        $this->checkEnvironment();

        // Step 2: Publish assets
        $this->publishAssets();

        // Step 3: Clear caches (if any)
        $this->clearCaches();

        // Step 4: Verify setup
        $this->verifySetup();

        echo str_repeat('=', 50) . PHP_EOL;
        echo "✅ Project setup completed successfully!" . PHP_EOL;
        echo "🌐 Visit http://localhost:8080 to view your portfolio" . PHP_EOL;
    }

    private function checkEnvironment(): void
    {
        echo "📋 Checking environment..." . PHP_EOL;

        // Check PHP version
        $phpVersion = PHP_VERSION;
        echo "  ✓ PHP version: {$phpVersion}" . PHP_EOL;

        if (version_compare($phpVersion, '8.1.0', '<')) {
            echo "  ❌ PHP 8.1+ required" . PHP_EOL;
            exit(1);
        }

        // Check .env file
        $envFile = __DIR__ . '/../../../../.env';
        if (file_exists($envFile)) {
            echo "  ✓ Environment file exists" . PHP_EOL;
        } else {
            echo "  ⚠️  .env file not found, using defaults" . PHP_EOL;
        }

        // Check pub directory permissions
        $pubDir = __DIR__ . '/../../../../pub';
        if (is_writable($pubDir)) {
            echo "  ✓ Public directory is writable" . PHP_EOL;
        } else {
            echo "  ❌ Public directory is not writable" . PHP_EOL;
            exit(1);
        }
    }

    private function publishAssets(): void
    {
        echo "📦 Publishing assets..." . PHP_EOL;
        
        $assetsCommand = new AssetsCommand();
        $assetsCommand->execute('assets:publish', []);
    }

    private function clearCaches(): void
    {
        echo "🧹 Clearing caches..." . PHP_EOL;
        
        // Clear any cache directories if they exist
        $cacheDirectories = [
            __DIR__ . '/../../../../cache',
            __DIR__ . '/../../../../tmp',
        ];

        foreach ($cacheDirectories as $cacheDir) {
            if (is_dir($cacheDir)) {
                $this->clearDirectory($cacheDir);
                echo "  ✓ Cleared cache: " . basename($cacheDir) . PHP_EOL;
            }
        }

        echo "  ✓ Cache clearing completed" . PHP_EOL;
    }

    private function verifySetup(): void
    {
        echo "🔍 Verifying setup..." . PHP_EOL;

        // Check if assets were published correctly
        $assetsDir = __DIR__ . '/../../../../pub/assets';
        $requiredAssets = [
            'base/css/reset.css',
            'base/css/variables.css', 
            'base/css/base.css',
            'frontend/css/theme.css'
        ];

        foreach ($requiredAssets as $asset) {
            $assetPath = $assetsDir . '/' . $asset;
            if (file_exists($assetPath)) {
                echo "  ✓ Asset exists: {$asset}" . PHP_EOL;
            } else {
                echo "  ❌ Missing asset: {$asset}" . PHP_EOL;
            }
        }

        // Check if modules have assets
        $modulesDir = $assetsDir . '/modules';
        if (is_dir($modulesDir)) {
            $moduleCount = 0;
            $modules = array_filter(
                scandir($modulesDir),
                fn($item) => $item !== '.' && $item !== '..' && is_dir($modulesDir . '/' . $item)
            );
            
            foreach ($modules as $module) {
                $moduleViewDir = $modulesDir . '/' . $module . '/view';
                if (is_dir($moduleViewDir)) {
                    $moduleCount++;
                    // Check for specific module assets
                    if (file_exists($moduleViewDir . '/frontend/css/' . $module . '.css')) {
                        echo "  ✓ {$module} module CSS published" . PHP_EOL;
                    }
                }
            }
            
            echo "  ✓ Module assets published: {$moduleCount} modules" . PHP_EOL;
        }
    }

    private function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $this->clearDirectory($filePath);
                rmdir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }
}
