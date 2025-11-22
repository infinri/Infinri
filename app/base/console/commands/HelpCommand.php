<?php
declare(strict_types=1);
/**
 * Help Command
 *
 * Displays available console commands and usage information
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class HelpCommand
{
    public function execute(string $command, array $args): void
    {
        echo "Portfolio Console Application" . PHP_EOL;
        echo str_repeat('=', 40) . PHP_EOL;
        echo PHP_EOL;
        
        echo "USAGE:" . PHP_EOL;
        echo "  php bin/console <command> [options]" . PHP_EOL;
        echo PHP_EOL;
        
        echo "AVAILABLE COMMANDS:" . PHP_EOL;
        echo PHP_EOL;
        
        echo "Asset Management:" . PHP_EOL;
        echo "  assets:publish      Publish all assets to pub/assets directory" . PHP_EOL;
        echo "  assets:clear        Clear all published assets" . PHP_EOL;
        echo "  assets:force-clear  Force clear assets (fixes permission issues)" . PHP_EOL;
        echo PHP_EOL;
        
        echo "Setup & Maintenance:" . PHP_EOL;
        echo "  setup:install (s:i)   Interactive project setup" . PHP_EOL;
        echo "  setup:update (s:up)   Deploy assets and setup project" . PHP_EOL;
        echo "  setup:minify (s:min)  Build production assets (local only)" . PHP_EOL;
        echo "  setup:permissions (s:p)  Fix file permissions" . PHP_EOL;
        echo PHP_EOL;
        
        echo "General:" . PHP_EOL;
        echo "  help                  Show this help message" . PHP_EOL;
        echo PHP_EOL;
        
        echo "WORKFLOW:" . PHP_EOL;
        echo "  Production Deploy:" . PHP_EOL;
        echo "    1. Set APP_ENV=production in .env" . PHP_EOL;
        echo "    2. php bin/console s:min         # Build minified bundles (2 files only)" . PHP_EOL;
        echo "    3. php bin/console s:up          # Verify production setup" . PHP_EOL;
        echo "    4. Site uses ONLY all.min.css & all.min.js (no individual files)" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "  Development Mode:" . PHP_EOL;
        echo "    1. Set APP_ENV=development in .env" . PHP_EOL;
        echo "    2. php bin/console assets:publish   # Publish individual files" . PHP_EOL;
        echo "    3. Edit source files, refresh browser (instant changes)" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "  On Production:" . PHP_EOL;
        echo "    1. git pull                      # Get latest code + assets" . PHP_EOL;
        echo "    2. php bin/console s:up          # Deploy assets" . PHP_EOL;
        echo PHP_EOL;
        
        echo "For more information, visit: https://github.com/infinri/Portfolio" . PHP_EOL;
    }
}
