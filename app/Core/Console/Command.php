<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Console;

use App\Core\Console\Concerns\WritesOutput;
use Throwable;

/**
 * Base Command
 *
 * All console commands should extend this class.
 * Commands self-describe for auto-discovery.
 */
abstract class Command
{
    use WritesOutput;
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

        return in_array($line, ['y', 'yes', '1', 'true'], true);
    }

    protected function askSecret(string $question): string
    {
        echo "  {$question}: ";

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

        $defaultIndex = array_search($default, $options, true) + 1;
        echo "  Select [" . $defaultIndex . "]: ";

        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line === '') {
            return $default;
        }

        $index = (int) $line - 1;

        return $options[$index] ?? $default;
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
            } catch (Throwable) {
            }
        }

        return dirname(__DIR__, 3);
    }
}
