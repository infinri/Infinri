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
        echo "  setup:update      Complete project setup (recommended)" . PHP_EOL;
        echo PHP_EOL;
        
        echo "General:" . PHP_EOL;
        echo "  help              Show this help message" . PHP_EOL;
        echo PHP_EOL;
        
        echo "EXAMPLES:" . PHP_EOL;
        echo "  php bin/console setup:update     # Complete setup" . PHP_EOL;
        echo "  php bin/console assets:publish   # Publish assets only" . PHP_EOL;
        echo "  php bin/console assets:clear     # Clear assets" . PHP_EOL;
        echo PHP_EOL;
        
        echo "For more information, visit: https://github.com/infinri/Portfolio" . PHP_EOL;
    }
}
