<?php declare(strict_types=1);
/**
 * Assets Build Command
 *
 * Builds minified production bundles using Node.js.
 *
 * IMPORTANT:
 * - Only run in development (requires node_modules)
 * - Bundles are committed to repo
 * - Production loads bundles directly, no build needed
 *
 * Usage:
 *   php bin/console assets:build    # Build minified bundles
 *
 * Output:
 *   pub/assets/dist/all.min.css
 *   pub/assets/dist/all.min.js
 *   pub/assets/dist/critical.min.css
 *
 * @package App\Core\Console\Commands
 */
namespace App\Core\Console\Commands;

use App\Core\Console\Command;

class AssetsBuildCommand extends Command
{
    protected string $name = 'assets:build';
    protected string $description = 'Build minified production bundles (dev only)';
    protected array $aliases = ['a:build'];

    public function handle(array $args = []): int
    {
        $this->line("⚡ Building Production Assets");
        $this->line(str_repeat('═', 50) . "\n");

        // Check if in production
        if (env('APP_ENV', 'development') === 'production') {
            $this->error("❌ Cannot build assets in production!");
            $this->line("   Bundles should be built in development and committed.\n");

            return 1;
        }

        // Step 1: Check Node.js
        if (! $this->checkNodeJs()) {
            return 1;
        }

        // Step 2: Check/install dependencies
        if (! $this->ensureDependencies()) {
            return 1;
        }

        // Step 3: Publish assets first (so build.js can find them)
        $this->publishAssets();

        // Step 4: Run build
        if (! $this->runBuild()) {
            return 1;
        }

        // Step 5: Verify output
        $this->verifyBuild();

        $this->line("\n" . str_repeat('═', 50));
        $this->info("✅ Production assets built successfully!\n");

        $this->line("📋 Next Steps:");
        $this->line("   1. Test locally: Set APP_ENV=production in .env");
        $this->line("   2. Verify bundles load correctly");
        $this->line("   3. Commit pub/assets/dist/ to repo");
        $this->line("   4. Deploy - production will use bundles\n");

        return 0;
    }

    /**
     * Check Node.js is available
     */
    private function checkNodeJs(): bool
    {
        $this->line("📋 Checking Node.js...");

        $nodeCheck = shell_exec('which node 2>/dev/null') ?: shell_exec('where node 2>nul');

        if (empty(trim($nodeCheck ?? ''))) {
            $this->line("   ❌ Node.js not found\n");
            $this->line("   Node.js is required to build assets.");
            $this->line("   Install from: https://nodejs.org/\n");

            return false;
        }

        $nodeVersion = trim(shell_exec('node --version 2>&1') ?? '');
        $this->line("   ✓ Node.js {$nodeVersion}\n");

        return true;
    }

    /**
     * Ensure npm dependencies are installed
     */
    private function ensureDependencies(): bool
    {
        $this->line("📦 Checking dependencies...");

        $nodeModulesDir = base_path('node_modules');

        if (! is_dir($nodeModulesDir)) {
            $this->line("   ℹ️  Installing npm dependencies...");

            $result = $this->runShellCommand('npm install');

            if ($result['exit_code'] !== 0) {
                $this->error("   ❌ Failed to install dependencies");
                $this->line(implode("\n", $result['output']));

                return false;
            }

            $this->line("   ✓ Dependencies installed\n");
        } else {
            $this->line("   ✓ Dependencies already installed\n");
        }

        return true;
    }

    /**
     * Publish assets before build
     */
    private function publishAssets(): void
    {
        $this->line("📁 Publishing assets...");

        $publishCommand = new AssetsPublishCommand();
        $publishCommand->handle(['assets:publish']);
    }

    /**
     * Run the build process
     */
    private function runBuild(): bool
    {
        $this->line("🔨 Building minified bundles...");

        $result = $this->runShellCommand('npm run build');

        if ($result['exit_code'] !== 0) {
            $this->error("   ❌ Build failed\n");
            $this->line("Build output:");
            $this->line(implode("\n", $result['output']));

            return false;
        }

        $this->line("   ✓ Build completed\n");

        return true;
    }

    /**
     * Verify build output
     */
    private function verifyBuild(): void
    {
        $this->line("🔍 Verifying build output...");

        $distDir = public_path('assets/dist');
        $requiredFiles = [
            'all.min.css',
            'all.min.js',
            'critical.min.css',
        ];

        $totalSize = 0;
        $allExists = true;

        foreach ($requiredFiles as $file) {
            $filePath = $distDir . '/' . $file;

            if (file_exists($filePath)) {
                $size = filesize($filePath);
                $totalSize += $size;
                $sizeKb = number_format($size / 1024, 1);
                $this->line("   ✓ {$file} ({$sizeKb} KB)");
            } else {
                $this->line("   ❌ Missing: {$file}");
                $allExists = false;
            }
        }

        if ($allExists) {
            $totalKb = number_format($totalSize / 1024, 1);
            $this->line("\n   📊 Total bundle size: {$totalKb} KB");
        }
    }

    /**
     * Run a shell command in the project root
     */
    private function runShellCommand(string $command): array
    {
        $rootDir = base_path();
        $output = [];
        $exitCode = 0;

        exec("cd {$rootDir} && {$command} 2>&1", $output, $exitCode);

        return [
            'output' => $output,
            'exit_code' => $exitCode,
        ];
    }
}
