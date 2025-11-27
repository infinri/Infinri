<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;

/**
 * Install Command
 * 
 * Interactive installation and .env setup wizard.
 */
class InstallCommand extends Command
{
    protected string $name = 'setup:install';
    protected string $description = 'Interactive .env setup wizard';
    protected array $aliases = ['s:i'];

    protected string $rootDir;
    protected string $envFile;

    public function __construct()
    {
        $this->rootDir = $this->getRootDir();
        $this->envFile = $this->rootDir . '/.env';
    }

    public function handle(array $args = []): int
    {
        $this->install();
        return 0;
    }

    protected function install(): void
    {
        $this->header("Project Installation");
        
        echo "\nThis wizard will configure your environment.\n";
        
        // Check if .env already exists
        if (file_exists($this->envFile)) {
            echo "\n⚠️  .env file already exists!\n";
            $overwrite = $this->confirm("Do you want to overwrite it?", false);
            
            if (!$overwrite) {
                echo "Installation cancelled.\n";
                return;
            }
            
            $backup = $this->envFile . '.backup.' . date('Y-m-d_H-i-s');
            copy($this->envFile, $backup);
            echo "✓ Backed up to: " . basename($backup) . "\n";
        }
        
        // Collect user-defined configuration
        $config = $this->collectUserConfig();
        
        // Auto-generate remaining configuration
        $config = $this->autoGenerateConfig($config);
        
        // Create .env file
        echo "\n📝 Creating .env file...\n";
        $this->createEnvFile($config);
        echo "✓ .env file created\n";
        
        // Fix permissions
        echo "\n🔒 Setting permissions...\n";
        $permsCmd = new PermissionsCommand();
        $permsCmd->execute('setup:permissions', []);
        
        // Test connections
        echo "\n🔍 Testing connections...\n";
        $this->testConnections($config);
        
        // Final message
        $this->showCompletionMessage($config);
    }

    protected function collectUserConfig(): array
    {
        $config = [];
        
        // ===================
        // Application
        // ===================
        echo "\n┌─────────────────────────────────────┐\n";
        echo "│  Application                        │\n";
        echo "└─────────────────────────────────────┘\n";
        
        $config['APP_ENV'] = $this->choice(
            "Environment",
            ['development', 'production'],
            'production'
        );
        
        $debugDefault = $config['APP_ENV'] === 'development';
        $config['APP_DEBUG'] = $this->confirm("Enable debug mode?", $debugDefault) ? 'true' : 'false';
        
        // ===================
        // Site
        // ===================
        echo "\n┌─────────────────────────────────────┐\n";
        echo "│  Site Configuration                 │\n";
        echo "└─────────────────────────────────────┘\n";
        
        $config['SITE_NAME'] = $this->ask("Site name", "My Application");
        $config['SITE_URL'] = $this->ask("Domain (e.g., example.com)", "localhost");
        
        echo "\n  Admin domain hides your admin panel from bots.\n";
        echo "  Example: admin-xyz123.example.com\n";
        $config['ADMIN_DOMAIN'] = $this->ask("Admin domain (leave empty to disable)", "");
        
        // ===================
        // Database
        // ===================
        echo "\n┌─────────────────────────────────────┐\n";
        echo "│  Database (PostgreSQL)              │\n";
        echo "└─────────────────────────────────────┘\n";
        
        $config['DB_CONNECTION'] = 'pgsql';
        $config['DB_HOST'] = $this->ask("Database host", "127.0.0.1");
        $config['DB_PORT'] = $this->ask("Database port", "5432");
        $config['DB_DATABASE'] = $this->ask("Database name", "app");
        $config['DB_USERNAME'] = $this->ask("Database username", "postgres");
        $config['DB_PASSWORD'] = $this->askSecret("Database password");
        
        // ===================
        // Redis
        // ===================
        echo "\n┌─────────────────────────────────────┐\n";
        echo "│  Redis                              │\n";
        echo "└─────────────────────────────────────┘\n";
        
        $config['REDIS_HOST'] = $this->ask("Redis host", "127.0.0.1");
        $config['REDIS_PORT'] = $this->ask("Redis port", "6379");
        $config['REDIS_PASSWORD'] = $this->askSecret("Redis password (leave empty if none)");
        $config['REDIS_DB'] = $this->ask("Redis database index", "0");
        
        return $config;
    }

    protected function autoGenerateConfig(array $config): array
    {
        $isProduction = $config['APP_ENV'] === 'production';
        
        // Auto-generated values
        $config['APP_VERSION'] = (string) time();
        $config['HTTPS_ONLY'] = $isProduction ? 'true' : 'false';
        $config['CSRF_ENABLED'] = 'true';
        $config['SESSION_LIFETIME'] = '7200';
        $config['SESSION_DOMAIN'] = $this->deriveSessionDomain($config['SITE_URL']);
        $config['SESSION_DRIVER'] = 'redis';
        $config['CACHE_DRIVER'] = 'redis';
        $config['QUEUE_CONNECTION'] = 'redis';
        $config['LOG_LEVEL'] = $isProduction ? 'warning' : 'debug';
        
        return $config;
    }

    protected function deriveSessionDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        
        // Localhost - no session domain needed
        if (in_array($domain, ['localhost', '127.0.0.1', '::1']) || filter_var($domain, FILTER_VALIDATE_IP)) {
            return '';
        }
        
        // Extract base domain for cookie sharing across subdomains
        $parts = explode('.', $domain);
        $count = count($parts);
        
        if ($count >= 2) {
            return '.' . $parts[$count - 2] . '.' . $parts[$count - 1];
        }
        
        return '';
    }

    protected function createEnvFile(array $config): void
    {
        $content = <<<'ENV'
# =============================================================================
# Environment Configuration
# =============================================================================
# Generated: {DATE}
# =============================================================================

# -----------------------------------------------------------------------------
# USER DEFINED - Application
# -----------------------------------------------------------------------------
APP_ENV={APP_ENV}
APP_DEBUG={APP_DEBUG}

# -----------------------------------------------------------------------------
# USER DEFINED - Site
# -----------------------------------------------------------------------------
SITE_NAME="{SITE_NAME}"
SITE_URL={SITE_URL}
ADMIN_DOMAIN={ADMIN_DOMAIN}

# -----------------------------------------------------------------------------
# USER DEFINED - Database (PostgreSQL)
# -----------------------------------------------------------------------------
DB_CONNECTION={DB_CONNECTION}
DB_HOST={DB_HOST}
DB_PORT={DB_PORT}
DB_DATABASE={DB_DATABASE}
DB_USERNAME={DB_USERNAME}
DB_PASSWORD={DB_PASSWORD}

# -----------------------------------------------------------------------------
# USER DEFINED - Redis
# -----------------------------------------------------------------------------
REDIS_HOST={REDIS_HOST}
REDIS_PORT={REDIS_PORT}
REDIS_PASSWORD={REDIS_PASSWORD}
REDIS_DB={REDIS_DB}

# =============================================================================
# AUTO GENERATED - Do not edit manually
# =============================================================================
APP_VERSION={APP_VERSION}
HTTPS_ONLY={HTTPS_ONLY}
CSRF_ENABLED={CSRF_ENABLED}
SESSION_LIFETIME={SESSION_LIFETIME}
SESSION_DOMAIN={SESSION_DOMAIN}
SESSION_DRIVER={SESSION_DRIVER}
CACHE_DRIVER={CACHE_DRIVER}
QUEUE_CONNECTION={QUEUE_CONNECTION}
LOG_LEVEL={LOG_LEVEL}

ENV;
        
        $content = str_replace('{DATE}', date('Y-m-d H:i:s'), $content);
        foreach ($config as $key => $value) {
            $content = str_replace('{' . $key . '}', (string)$value, $content);
        }
        
        file_put_contents($this->envFile, $content);
        chmod($this->envFile, 0600); // Secure permissions for sensitive data
    }

    protected function testConnections(array $config): void
    {
        // Test PostgreSQL
        echo "  PostgreSQL: ";
        try {
            $dsn = "pgsql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_DATABASE']}";
            $pdo = new \PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], [
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            echo "✓ Connected\n";
        } catch (\Throwable $e) {
            echo "✗ Failed - " . $e->getMessage() . "\n";
        }
        
        // Test Redis
        echo "  Redis: ";
        try {
            $socket = @fsockopen($config['REDIS_HOST'], (int)$config['REDIS_PORT'], $errno, $errstr, 3);
            if ($socket) {
                fclose($socket);
                echo "✓ Connected\n";
            } else {
                echo "✗ Failed - {$errstr}\n";
            }
        } catch (\Throwable $e) {
            echo "✗ Failed - " . $e->getMessage() . "\n";
        }
    }

    protected function showCompletionMessage(array $config): void
    {
        echo "\n";
        $this->header("Installation Complete!");
        
        echo "\n✅ Environment configured successfully!\n";
        echo "\n";
        echo "Configuration Summary:\n";
        echo "  • Environment: {$config['APP_ENV']}\n";
        echo "  • Site: {$config['SITE_NAME']} ({$config['SITE_URL']})\n";
        echo "  • Database: {$config['DB_DATABASE']}@{$config['DB_HOST']}\n";
        echo "  • Redis: {$config['REDIS_HOST']}:{$config['REDIS_PORT']}\n";
        
        if (!empty($config['ADMIN_DOMAIN'])) {
            echo "  • Admin: {$config['ADMIN_DOMAIN']}\n";
        }
        
        echo "\nNext steps:\n";
        echo "  1. php bin/console s:up    # Setup and publish assets\n";
        echo "  2. Start your web server\n";
        
        $protocol = $config['HTTPS_ONLY'] === 'true' ? 'https' : 'http';
        echo "  3. Visit: {$protocol}://{$config['SITE_URL']}\n";
        echo "\n";
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

    // =========================================================================
    // Additional UI Helpers (not in base Command)
    // =========================================================================

    protected function askSecret(string $question): string
    {
        echo "  {$question}: ";
        
        // Try to hide input on Unix systems
        if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
            system('stty -echo');
            $handle = fopen('php://stdin', 'r');
            $line = trim(fgets($handle));
            fclose($handle);
            system('stty echo');
            echo "\n";
        } else {
            $handle = fopen('php://stdin', 'r');
            $line = trim(fgets($handle));
            fclose($handle);
        }
        
        return $line;
    }

    protected function choice(string $question, array $options, string $default): string
    {
        echo "  {$question}:\n";
        foreach ($options as $i => $option) {
            $marker = $option === $default ? '●' : '○';
            echo "    {$marker} " . ($i + 1) . ". {$option}\n";
        }
        
        $defaultIndex = array_search($default, $options) + 1;
        echo "  Select [" . $defaultIndex . "]: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line === '') {
            return $default;
        }
        
        $index = (int)$line - 1;
        return $options[$index] ?? $default;
    }
}
