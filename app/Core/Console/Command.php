<?php

declare(strict_types=1);

namespace App\Core\Console;

/**
 * Base Command
 * 
 * All console commands should extend this class.
 * Commands self-describe for auto-discovery.
 */
abstract class Command
{
    /**
     * Command name (e.g., 'setup:install')
     */
    protected string $name = '';

    /**
     * Command description for help
     */
    protected string $description = '';

    /**
     * Command aliases (e.g., ['s:i'])
     */
    protected array $aliases = [];

    /**
     * Execute the command
     */
    abstract public function handle(array $args = []): int;

    /**
     * Legacy execute method for compatibility
     */
    public function execute(string $command, array $args): int
    {
        return $this->handle($args);
    }

    /**
     * Get command name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get command description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get command aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    // =========================================================================
    // Output Helpers
    // =========================================================================

    protected function line(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m" . PHP_EOL;
    }

    protected function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m" . PHP_EOL;
    }

    protected function header(string $text): void
    {
        $length = strlen($text) + 4;
        $border = str_repeat('═', $length);
        echo "\n╔{$border}╗\n";
        echo "║  {$text}  ║\n";
        echo "╚{$border}╝\n";
    }

    protected function ask(string $question, string $default = ''): string
    {
        $defaultText = $default !== '' ? " [{$default}]" : '';
        echo "  {$question}{$defaultText}: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);
        
        return $line !== '' ? $line : $default;
    }

    protected function confirm(string $question, bool $default = true): bool
    {
        $hint = $default ? 'Y/n' : 'y/N';
        echo "  {$question} [{$hint}]: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = strtolower(trim(fgets($handle)));
        fclose($handle);
        
        if ($line === '') {
            return $default;
        }
        
        return in_array($line, ['y', 'yes', '1', 'true']);
    }

    // =========================================================================
    // Path Helpers
    // =========================================================================

    /**
     * Get the application root directory
     */
    protected function getRootDir(): string
    {
        if (function_exists('app')) {
            try {
                return app()->basePath();
            } catch (\Throwable) {}
        }
        return dirname(__DIR__, 3);
    }
}
