<?php
declare(strict_types=1);
/**
 * Minify Command
 *
 * Builds and minifies production assets locally
 * Run this ONLY on development machine before deploying
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class MinifyCommand
{
    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'setup:minify':
                $this->minifyAssets();
                break;
            default:
                echo "Unknown minify command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function minifyAssets(): void
    {
        echo "⚡ Building Production Assets" . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;

        // Step 1: Check Node.js
        $this->checkNodeJs();

        // Step 2: Install dependencies if needed
        $this->ensureDependencies();

        // Step 3: Build minified bundles
        $this->buildAssets();

        // Step 4: Verify output
        $this->verifyBuild();

        echo str_repeat('=', 50) . PHP_EOL;
        echo "✅ Production assets built successfully!" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "📋 Next Steps:" . PHP_EOL;
        echo "  1. Test locally with: APP_ENV=production" . PHP_EOL;
        echo "  2. Commit: git add pub/assets/dist/" . PHP_EOL;
        echo "  3. Commit: git commit -m 'build: Update production assets'" . PHP_EOL;
        echo "  4. Push: git push origin main" . PHP_EOL;
        echo "  5. Deploy: git pull && php bin/console s:up" . PHP_EOL;
        echo "" . PHP_EOL;
    }

    private function checkNodeJs(): void
    {
        echo "📋 Checking Node.js..." . PHP_EOL;

        $nodeCheck = shell_exec('which node 2>/dev/null') ?: shell_exec('where node 2>nul');
        
        if (empty(trim($nodeCheck))) {
            echo "  ❌ Node.js not found" . PHP_EOL;
            echo "" . PHP_EOL;
            echo "Node.js is required to build production assets." . PHP_EOL;
            echo "Install from: https://nodejs.org/" . PHP_EOL;
            exit(1);
        }

        $nodeVersion = trim(shell_exec('node --version 2>&1') ?? '');
        echo "  ✓ Node.js version: {$nodeVersion}" . PHP_EOL;
    }

    private function ensureDependencies(): void
    {
        echo "📦 Checking dependencies..." . PHP_EOL;

        $nodeModulesDir = __DIR__ . '/../../../../node_modules';
        
        if (!is_dir($nodeModulesDir)) {
            echo "  ℹ️  Installing Node.js dependencies..." . PHP_EOL;
            $this->runCommand('npm install');
            echo "  ✓ Dependencies installed" . PHP_EOL;
        } else {
            echo "  ✓ Dependencies already installed" . PHP_EOL;
        }
    }

    private function buildAssets(): void
    {
        echo "🔨 Building minified bundles..." . PHP_EOL;

        $result = $this->runCommand('npm run build');
        
        if ($result['exit_code'] !== 0) {
            echo "  ❌ Build failed" . PHP_EOL;
            echo "" . PHP_EOL;
            echo "Build output:" . PHP_EOL;
            echo implode(PHP_EOL, $result['output']) . PHP_EOL;
            exit(1);
        }

        echo "  ✓ Build completed successfully" . PHP_EOL;
    }

    private function verifyBuild(): void
    {
        echo "🔍 Verifying build output..." . PHP_EOL;

        $distDir = __DIR__ . '/../../../../pub/assets/dist';
        $requiredFiles = [
            'all.min.css',
            'all.min.js',
            'base.min.css',
            'base.min.js'
        ];

        $allExists = true;
        foreach ($requiredFiles as $file) {
            $filePath = $distDir . '/' . $file;
            if (file_exists($filePath)) {
                $size = filesize($filePath);
                $sizeKb = number_format($size / 1024, 1);
                echo "  ✓ {$file} ({$sizeKb} KB)" . PHP_EOL;
            } else {
                echo "  ❌ Missing: {$file}" . PHP_EOL;
                $allExists = false;
            }
        }

        if (!$allExists) {
            echo "" . PHP_EOL;
            echo "❌ Build verification failed - some files are missing" . PHP_EOL;
            exit(1);
        }
    }

    private function runCommand(string $command): array
    {
        $rootDir = __DIR__ . '/../../../../';
        $output = [];
        $exitCode = 0;
        
        exec("cd {$rootDir} && {$command} 2>&1", $output, $exitCode);
        
        return [
            'output' => $output,
            'exit_code' => $exitCode
        ];
    }
}
