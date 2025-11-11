<?php
declare(strict_types=1);
/**
 * Assets Command
 *
 * Manages asset publishing and clearing operations
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class AssetsCommand
{
    private const PUB_ASSETS_DIR = __DIR__ . '/../../../../pub/assets';
    private const APP_BASE_VIEW = __DIR__ . '/../../view';
    private const APP_MODULES_DIR = __DIR__ . '/../../../modules';

    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'assets:publish':
                $this->publishAssets();
                break;
            case 'assets:clear':
                $this->clearAssets();
                break;
            case 'assets:force-clear':
                $this->forceClearAssets();
                break;
            default:
                echo "Unknown assets command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function publishAssets(): void
    {
        echo "Publishing assets..." . PHP_EOL;

        // Ensure pub/assets directory exists
        $this->ensureDirectory(self::PUB_ASSETS_DIR);

        // Clear existing assets
        $this->clearAssets();

        // Copy base assets
        $this->copyDirectory(
            self::APP_BASE_VIEW . '/base',
            self::PUB_ASSETS_DIR . '/base'
        );
        echo "✓ Published base assets" . PHP_EOL;

        // Copy frontend assets
        $this->copyDirectory(
            self::APP_BASE_VIEW . '/frontend',
            self::PUB_ASSETS_DIR . '/frontend'
        );
        echo "✓ Published frontend assets" . PHP_EOL;

        // Copy module assets
        $this->publishModuleAssets();

        echo "✅ Assets published successfully!" . PHP_EOL;
    }

    private function publishModuleAssets(): void
    {
        $modulesDir = self::APP_MODULES_DIR;
        $pubModulesDir = self::PUB_ASSETS_DIR . '/modules';

        $this->ensureDirectory($pubModulesDir);

        if (!is_dir($modulesDir)) {
            echo "⚠️  Modules directory not found: {$modulesDir}" . PHP_EOL;
            return;
        }

        $modules = array_filter(
            scandir($modulesDir),
            fn($item) => $item !== '.' && $item !== '..' && is_dir($modulesDir . '/' . $item)
        );

        foreach ($modules as $module) {
            $moduleViewDir = $modulesDir . '/' . $module . '/view';
            $pubModuleDir = $pubModulesDir . '/' . $module . '/view';

            if (is_dir($moduleViewDir)) {
                $this->copyDirectory($moduleViewDir, $pubModuleDir);
                echo "✓ Published {$module} module assets" . PHP_EOL;
            }
        }
    }

    private function clearAssets(): void
    {
        echo "Preparing assets directory..." . PHP_EOL;

        if (is_dir(self::PUB_ASSETS_DIR)) {
            // Instead of trying to remove everything, just clean what we can
            $this->cleanAssetsDirectory(self::PUB_ASSETS_DIR);
        }

        // Ensure the directory exists for new assets
        $this->ensureDirectory(self::PUB_ASSETS_DIR);
        
        echo "✓ Assets directory prepared" . PHP_EOL;
    }

    private function forceClearAssets(): void
    {
        echo "Force clearing assets with permission fixes..." . PHP_EOL;

        if (is_dir(self::PUB_ASSETS_DIR)) {
            // Use system command for force removal if available
            $assetsPath = escapeshellarg(self::PUB_ASSETS_DIR);
            
            if (function_exists('exec') && !empty(shell_exec('which rm'))) {
                echo "Using system rm command..." . PHP_EOL;
                exec("rm -rf {$assetsPath} 2>/dev/null", $output, $returnCode);
                
                if ($returnCode === 0) {
                    echo "✓ Force cleared with system command" . PHP_EOL;
                } else {
                    echo "⚠️  System command failed, trying PHP method..." . PHP_EOL;
                    $this->removeDirectory(self::PUB_ASSETS_DIR);
                }
            } else {
                echo "System rm not available, using PHP method..." . PHP_EOL;
                $this->removeDirectory(self::PUB_ASSETS_DIR);
            }
        }

        // Ensure the directory exists for new assets
        $this->ensureDirectory(self::PUB_ASSETS_DIR);
        
        echo "✅ Force clear completed!" . PHP_EOL;
    }

    private function cleanAssetsDirectory(string $dir): void
    {
        // Only clean files we know we can regenerate
        $cleanablePatterns = [
            '*.css',
            '*.js',
            '*.map'
        ];

        $dirsToClean = [
            $dir . '/base',
            $dir . '/frontend',
            $dir . '/modules'
        ];

        foreach ($dirsToClean as $cleanDir) {
            if (is_dir($cleanDir)) {
                $this->cleanDirectoryContents($cleanDir, $cleanablePatterns);
            }
        }
    }

    private function cleanDirectoryContents(string $dir, array $patterns): void
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                $path = $item->getRealPath();
                $filename = $item->getFilename();
                
                if ($item->isFile()) {
                    // Check if file matches our cleanable patterns
                    foreach ($patterns as $pattern) {
                        if (fnmatch($pattern, $filename)) {
                            @chmod($path, 0644);
                            @unlink($path);
                            break;
                        }
                    }
                } elseif ($item->isDir()) {
                    // Try to remove empty directories
                    @chmod($path, 0755);
                    @rmdir($path); // Will only succeed if empty
                }
            }
        } catch (\Exception $e) {
            // Silently continue - cleaning is best effort
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            echo "⚠️  Source directory not found: {$source}" . PHP_EOL;
            return;
        }

        $this->ensureDirectory($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                $this->ensureDirectory($destPath);
            } else {
                copy($item->getRealPath(), $destPath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            $failedItems = [];

            foreach ($iterator as $item) {
                $path = $item->getRealPath();
                
                if ($item->isDir()) {
                    // Try to change permissions before removing
                    @chmod($path, 0755);
                    if (!@rmdir($path)) {
                        $failedItems[] = $path;
                    }
                } else {
                    // Try to change permissions before removing
                    @chmod($path, 0644);
                    if (!@unlink($path)) {
                        $failedItems[] = $path;
                    }
                }
            }

            // Try to remove the main directory
            @chmod($dir, 0755);
            if (!@rmdir($dir)) {
                $failedItems[] = $dir;
            }

            // Only show warnings if there were actual failures and we're in verbose mode
            if (!empty($failedItems) && isset($_ENV['VERBOSE_ASSETS'])) {
                echo "⚠️  Some files couldn't be removed (this is usually normal)" . PHP_EOL;
            }
        } catch (\Exception $e) {
            // Only show error in verbose mode
            if (isset($_ENV['VERBOSE_ASSETS'])) {
                echo "⚠️  Error clearing assets: " . $e->getMessage() . PHP_EOL;
            }
        }
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
