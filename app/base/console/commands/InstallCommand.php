<?php
declare(strict_types=1);
/**
 * Install Command
 *
 * Interactive installation and .env setup wizard
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class InstallCommand
{
    private string $rootDir;
    private string $envFile;

    public function __construct()
    {
        $this->rootDir = dirname(__DIR__, 4); // Go up 4 levels to project root
        $this->envFile = $this->rootDir . '/.env';
    }

    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'setup:install':
            case 's:i':
                $this->install();
                break;
            default:
                echo "Unknown install command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function install(): void
    {
        $this->header("Portfolio Project Installation");
        
        echo "\n";
        echo "This wizard will help you set up your portfolio project.\n";
        echo "\n";
        
        // Check if .env already exists
        if (file_exists($this->envFile)) {
            echo "⚠️  .env file already exists!\n";
            $overwrite = $this->confirm("Do you want to overwrite it?", false);
            
            if (!$overwrite) {
                echo "Installation cancelled.\n";
                return;
            }
            
            // Backup existing .env
            $backup = $this->envFile . '.backup.' . date('Y-m-d_H-i-s');
            copy($this->envFile, $backup);
            echo "✓ Existing .env backed up to: " . basename($backup) . "\n\n";
        }
        
        // Collect configuration
        $config = $this->collectConfiguration();
        
        // Create .env file
        echo "\nCreating .env file...\n";
        $this->createEnvFile($config);
        echo "✓ .env file created successfully!\n";
        
        // Fix permissions
        echo "\nSetting file permissions...\n";
        $permsCmd = new PermissionsCommand();
        $permsCmd->execute('setup:permissions', []);
        
        // Check dependencies
        echo "\nChecking dependencies...\n";
        $this->checkDependencies();
        
        // Final message
        echo "\n";
        $this->header("Installation Complete!");
        echo "✅ Your portfolio project is ready to use.\n";
        echo "\n";
        echo "Next steps:\n";
        echo "  1. Run: bin/console s:up to publish assets\n";
        echo "  2. Start server: caddy run\n";
        echo "  3. Visit: http://localhost:8080\n";
        echo "\n";
    }

    private function collectConfiguration(): array
    {
        $config = [];
        
        // Environment
        echo "\n▸ Environment Configuration\n";
        $config['APP_ENV'] = $this->choice(
            "What environment is this?",
            ['development', 'production'],
            'development'
        );
        
        // Site information
        echo "\n▸ Site Information\n";
        $config['SITE_NAME'] = $this->ask("Site name", "Infinri Portfolio");
        
        // Accept user-friendly domain input
        echo "Enter your domain (e.g., 'infinri.com' or 'localhost')\n";
        $userInput = $this->ask("Site URL", "localhost");
        $config['SITE_URL'] = $this->normalizeSiteUrl($userInput);
        
        // Show what we normalized it to
        if ($userInput !== $config['SITE_URL']) {
            echo "→ Normalized to: {$config['SITE_URL']}\n";
        }
        
        // Security
        echo "\n▸ Security Settings\n";
        $config['CSRF_ENABLED'] = $this->confirm("Enable CSRF protection?", true) ? 'true' : 'false';
        
        // HTTPS_ONLY should default based on environment
        $httpsDefault = ($config['APP_ENV'] === 'production');
        echo ($httpsDefault ? "Production requires HTTPS" : "Development typically uses HTTP") . "\n";
        $config['HTTPS_ONLY'] = $this->confirm("HTTPS only?", $httpsDefault) ? 'true' : 'false';
        
        // Session
        echo "\n▸ Session Configuration\n";
        $config['SESSION_LIFETIME'] = $this->ask("Session lifetime (seconds)", "7200");
        
        // Smart default for SESSION_DOMAIN based on SITE_URL
        $defaultSessionDomain = $this->deriveSessionDomain($config['SITE_URL']);
        echo "Suggested session domain: " . ($defaultSessionDomain ?: "(empty - works for localhost)") . "\n";
        $customDomain = $this->ask("Override session domain? (press Enter to use suggestion)", "");
        $config['SESSION_DOMAIN'] = $customDomain ?: $defaultSessionDomain;
        
        // Version - Auto-generate timestamp
        $config['APP_VERSION'] = time();
        
        return $config;
    }

    private function normalizeSiteUrl(string $input): string
    {
        $input = trim($input);
        
        // Already has protocol
        if (preg_match('#^https?://#i', $input)) {
            return $input;
        }
        
        // Localhost without port - add default
        if (preg_match('#^(localhost|127\.0\.0\.1|::1)$#i', $input)) {
            return 'http://' . $input . ':8080';
        }
        
        // Localhost with port - use http
        if (preg_match('#^(localhost|127\.0\.0\.1|::1):\d+$#i', $input)) {
            return 'http://' . $input;
        }
        
        // Production domain - use https
        return 'https://' . $input;
    }

    private function deriveSessionDomain(string $siteUrl): string
    {
        // Parse the URL
        $parsed = parse_url($siteUrl);
        if (!$parsed || !isset($parsed['host'])) {
            return '';
        }
        
        $host = strtolower($parsed['host']);
        
        // Localhost or IP address - use empty (default)
        if ($host === 'localhost' || 
            $host === '127.0.0.1' || 
            $host === '::1' ||
            filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }
        
        // Production domain - add leading dot for subdomain cookie sharing
        $parts = explode('.', $host);
        $count = count($parts);
        
        if ($count >= 2) {
            // Get last 2 parts (example.com from subdomain.example.com)
            $baseDomain = $parts[$count - 2] . '.' . $parts[$count - 1];
            return '.' . $baseDomain;
        }
        
        // Single word domain (rare) - use as-is with leading dot
        return '.' . $host;
    }

    private function createEnvFile(array $config): void
    {
        $content = <<<ENV
# Portfolio Project Configuration
# Generated: {DATE}

# Environment (development or production)
APP_ENV={APP_ENV}

# Site Information
SITE_NAME="{SITE_NAME}"
SITE_URL={SITE_URL}

# Security
CSRF_ENABLED={CSRF_ENABLED}
HTTPS_ONLY={HTTPS_ONLY}

# Session Configuration
SESSION_LIFETIME={SESSION_LIFETIME}
SESSION_DOMAIN={SESSION_DOMAIN}

# Cache Busting Version
APP_VERSION={APP_VERSION}

ENV;
        
        // Replace placeholders
        $content = str_replace('{DATE}', date('Y-m-d H:i:s'), $content);
        foreach ($config as $key => $value) {
            $content = str_replace('{' . $key . '}', (string)$value, $content);
        }
        
        file_put_contents($this->envFile, $content);
        chmod($this->envFile, 0644);
    }

    private function checkDependencies(): void
    {
        $checks = [
            'PHP' => ['php', '--version'],
            'Node.js' => ['node', '--version'],
            'npm' => ['npm', '--version'],
            'Caddy' => ['caddy', 'version'],
        ];
        
        foreach ($checks as $name => $command) {
            $output = @shell_exec(implode(' ', $command) . ' 2>&1');
            if ($output) {
                preg_match('/[\d\.]+/', $output, $matches);
                $version = $matches[0] ?? 'installed';
                echo "  ✓ {$name} ({$version})\n";
            } else {
                echo "  ✗ {$name} not found\n";
            }
        }
    }

    // ==================== UI Helpers ====================

    private function header(string $text): void
    {
        $length = strlen($text) + 4;
        $border = str_repeat('═', $length);
        echo "\n╔{$border}╗\n";
        echo "║  {$text}  ║\n";
        echo "╚{$border}╝\n";
    }

    private function ask(string $question, string $default = ''): string
    {
        $defaultText = $default ? " [{$default}]" : '';
        echo "  {$question}{$defaultText}: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);
        
        return $line ?: $default;
    }

    private function choice(string $question, array $options, string $default): string
    {
        echo "  {$question}\n";
        foreach ($options as $i => $option) {
            $marker = $option === $default ? '*' : ' ';
            echo "    {$marker} " . ($i + 1) . ". {$option}\n";
        }
        echo "  Choice [" . (array_search($default, $options) + 1) . "]: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line === '') {
            return $default;
        }
        
        $index = (int)$line - 1;
        return $options[$index] ?? $default;
    }

    private function confirm(string $question, bool $default = true): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo "  {$question} [{$defaultText}]: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = strtolower(trim(fgets($handle)));
        fclose($handle);
        
        if ($line === '') {
            return $default;
        }
        
        return in_array($line, ['y', 'yes', '1', 'true']);
    }
}
