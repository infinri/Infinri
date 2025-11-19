<?php
declare(strict_types=1);
/**
 * Permissions Command
 *
 * Fix file permissions for the portfolio project
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class PermissionsCommand
{
    private string $rootDir;

    public function __construct()
    {
        $this->rootDir = dirname(__DIR__, 4); // Go up 4 levels to project root
    }

    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'setup:permissions':
            case 's:p':
                $this->fixPermissions();
                break;
            default:
                echo "Unknown permissions command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function fixPermissions(): void
    {
        echo "🔒 Fixing file permissions..." . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;
        
        // Directories to create/fix
        $directories = [
            'var/log' => 0755,
            'var/cache' => 0755,
            'var/sessions' => 0770,  // 770 so PHP-FPM (www-data group) can write sessions
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
        
        // Fix .env file permissions (FILE not directory!)
        $envFile = $this->rootDir . '/.env';
        if (file_exists($envFile) && is_file($envFile)) {
            chmod($envFile, 0644);
            echo "  ✓ Set .env to 0644" . PHP_EOL;
        }
        
        // Set var/sessions group to www-data for PHP-FPM access
        $sessionsPath = $this->rootDir . '/var/sessions';
        if (file_exists($sessionsPath)) {
            // Try to set group ownership (may need sudo)
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
