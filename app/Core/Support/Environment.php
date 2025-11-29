<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Support;

/**
 * Environment Loader
 * 
 * Loads environment variables from .env file
 */
class Environment
{
    /**
     * The directory where the .env file is located
     *
     * @var string
     */
    protected string $path;

    /**
     * The name of the .env file
     *
     * @var string
     */
    protected string $file;

    /**
     * Create a new environment loader
     *
     * @param string $path The directory path
     * @param string $file The filename
     */
    public function __construct(string $path, string $file = '.env')
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->file = $file;
    }

    /**
     * Load the environment file
     *
     * @return void
     * @throws \RuntimeException
     */
    public function load(): void
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $this->file;

        if (!is_file($filePath)) {
            throw new \RuntimeException("Environment file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException("Environment file is not readable: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new \RuntimeException("Failed to read environment file: {$filePath}");
        }

        foreach ($lines as $line) {
            $this->processLine($line);
        }
    }

    /**
     * Process a single line from the .env file
     *
     * @param string $line
     * @return void
     */
    protected function processLine(string $line): void
    {
        // Skip comments and empty lines
        $line = trim($line);
        
        if (empty($line) || str_starts_with($line, '#')) {
            return;
        }

        // Parse KEY=VALUE format
        if (!str_contains($line, '=')) {
            return;
        }

        [$name, $value] = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);

        // Don't override existing environment variables
        if (getenv($name) !== false) {
            return;
        }

        // Remove quotes from value
        $value = $this->stripQuotes($value);

        // Set the environment variable
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Strip quotes from a string
     *
     * @param string $value
     * @return string
     */
    protected function stripQuotes(string $value): string
    {
        $value = trim($value);

        // Check for matching quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Get the full path to the environment file
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->file;
    }
}
