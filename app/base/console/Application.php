<?php
declare(strict_types=1);
/**
 * Console Application
 *
 * Handles command-line interface operations
 *
 * @package App\Console
 */

namespace App\Console;

use App\Console\Commands\{AssetsCommand, SetupCommand, MinifyCommand, HelpCommand};

final class Application
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->commands = [
            'assets:publish' => AssetsCommand::class,
            'assets:clear' => AssetsCommand::class,
            'assets:force-clear' => AssetsCommand::class,
            'setup:update' => SetupCommand::class,
            's:up' => SetupCommand::class,
            'setup:minify' => MinifyCommand::class,
            's:min' => MinifyCommand::class,
            'help' => HelpCommand::class,
        ];
    }

    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        if (!isset($this->commands[$command])) {
            echo "Unknown command: {$command}" . PHP_EOL;
            echo "Run 'help' to see available commands." . PHP_EOL;
            exit(1);
        }

        $commandClass = $this->commands[$command];
        $commandInstance = new $commandClass();
        
        if (method_exists($commandInstance, 'execute')) {
            $commandInstance->execute($command, $args);
        } else {
            echo "Command {$command} is not properly implemented." . PHP_EOL;
            exit(1);
        }
    }
}
