<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Support\EnvManager;
use App\Core\Security\CookieEncrypter;

/**
 * Key Generate Command
 * 
 * Generates a secure application key for encryption.
 * 
 * Usage:
 *   php bin/console key:generate          # Generate and save to .env
 *   php bin/console key:generate --show   # Only display the key
 *   php bin/console key:generate --force  # Overwrite existing key
 */
class KeyGenerateCommand extends Command
{
    protected string $name = 'key:generate';
    protected string $description = 'Generate a new application encryption key';
    protected array $aliases = ['key:gen'];

    public function handle(array $args = []): int
    {
        $showOnly = in_array('--show', $args);
        $force = in_array('--force', $args);

        // Generate the key
        $key = CookieEncrypter::generateKey();

        if ($showOnly) {
            echo "\n🔑 Generated Key:\n";
            echo "   {$key}\n\n";
            echo "Add this to your .env file as APP_KEY={$key}\n\n";
            return 0;
        }

        $rootDir = $this->getRootDir();
        $envFile = $rootDir . '/.env';

        if (!file_exists($envFile)) {
            echo "\n❌ .env file not found!\n";
            echo "   Run 'php bin/console s:i' first to create it.\n\n";
            return 1;
        }

        $env = new EnvManager($envFile);
        $existingKey = $env->get('APP_KEY');

        if (!empty($existingKey) && !$force) {
            echo "\n⚠️  APP_KEY already exists!\n";
            echo "   Use --force to overwrite.\n";
            echo "   Current key: " . substr($existingKey, 0, 20) . "...\n\n";
            return 1;
        }

        // Save to .env
        $env->persist('APP_KEY', $key);

        echo "\n✅ Application key generated!\n";
        echo "   Key: {$key}\n\n";

        if (!empty($existingKey)) {
            echo "⚠️  Previous key was overwritten.\n";
            echo "   Existing encrypted cookies will be invalidated.\n\n";
        }

        return 0;
    }
}
