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
        echo "  setup:update (s:up)   Deploy assets and setup project" . PHP_EOL;
        echo "  setup:minify (s:min)  Build production assets (local only)" . PHP_EOL;
        echo PHP_EOL;
        
        echo "General:" . PHP_EOL;
        echo "  help                  Show this help message" . PHP_EOL;
        echo PHP_EOL;
        
        echo "WORKFLOW:" . PHP_EOL;
        echo "  Local Development:" . PHP_EOL;
        echo "    1. php bin/console s:min         # Build production assets" . PHP_EOL;
        echo "    2. git commit & push             # Commit built assets" . PHP_EOL;
        echo "    3. Deploy to production          # Git pull on server" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "  On Production:" . PHP_EOL;
        echo "    1. git pull                      # Get latest code + assets" . PHP_EOL;
        echo "    2. php bin/console s:up          # Deploy assets" . PHP_EOL;
        echo PHP_EOL;
        
        echo "For more information, visit: https://github.com/infinri/Portfolio" . PHP_EOL;
    }
}
