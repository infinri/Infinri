<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;

/**
 * Permissions Command
 * 
 * Fix file and directory permissions.
 */
class PermissionsCommand extends Command
{
    protected string $name = 'setup:permissions';
    protected string $description = 'Fix file and directory permissions';
    protected array $aliases = ['s:p'];

    protected string $rootDir;

    public function __construct()
    {
        $this->rootDir = $this->getRootDir();
    }

    public function handle(array $args = []): int
    {
        $this->fixPermissions();
        return 0;
    }

    protected function fixPermissions(): void
    {
        echo "🔒 Fixing file permissions..." . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;
        
        // Directories to create/fix
        $directories = [
            'var/log' => 0755,
            'var/cache' => 0755,
            'var/sessions' => 0770,
            'pub/assets/dist' => 0755,
        ];
        
        foreach ($directories as $path => $mode) {
            $fullPath = $this->rootDir . '/' . $path;
            
            if (!file_exists($fullPath)) {
                mkdir($fullPath, $mode, true);
                echo "  ✓ Created {$path}" . PHP_EOL;
            } else {
                chmod($fullPath, $mode);
                echo "  ✓ Set {$path} to " . decoct($mode) . PHP_EOL;
            }
        }
        
        // Fix .env file permissions
        $envFile = $this->rootDir . '/.env';
        if (file_exists($envFile) && is_file($envFile)) {
            chmod($envFile, 0644);
            echo "  ✓ Set .env to 0644" . PHP_EOL;
        }
        
        // Set var/sessions group to www-data for PHP-FPM access
        $sessionsPath = $this->rootDir . '/var/sessions';
        if (file_exists($sessionsPath)) {
            @chgrp($sessionsPath, 'www-data');
            echo "  ✓ Set var/sessions group to www-data" . PHP_EOL;
        }
        
        // Make bin scripts executable
        $binScripts = glob($this->rootDir . '/bin/*');
        if ($binScripts) {
            foreach ($binScripts as $script) {
                chmod($script, 0755);
            }
            echo "  ✓ Made bin scripts executable" . PHP_EOL;
        }
        
        echo str_repeat('=', 50) . PHP_EOL;
        echo "✅ Permissions updated successfully!" . PHP_EOL;
    }
}
