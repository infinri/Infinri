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
namespace App\Core\Support;

/**
 * Environment Manager
 *
 * Handles reading and writing .env file values.
 */
class EnvManager
{
    protected string $envFile;
    protected array $vars = [];
    protected bool $loaded = false;

    public function __construct(?string $envFile = null)
    {
        $this->envFile = $envFile ?? $this->getDefaultEnvFile();
    }

    /**
     * Get an environment variable
     */
    public function get(string $key, string $default = ''): string
    {
        $this->load();

        return $this->vars[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Set an environment variable in memory
     */
    public function set(string $key, string $value): void
    {
        $this->load();
        $this->vars[$key] = $value;
    }

    /**
     * Check if a key exists
     */
    public function has(string $key): bool
    {
        $this->load();

        return isset($this->vars[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Get all loaded variables
     */
    public function all(): array
    {
        $this->load();

        return $this->vars;
    }

    /**
     * Update a value in the .env file
     */
    public function persist(string $key, string $value): bool
    {
        if (! file_exists($this->envFile)) {
            return false;
        }

        $content = file_get_contents($this->envFile);

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        $result = file_put_contents($this->envFile, $content) !== false;

        if ($result) {
            $this->vars[$key] = $value;
        }

        return $result;
    }

    /**
     * Reload from file
     */
    public function reload(): void
    {
        $this->loaded = false;
        $this->vars = [];
        $this->load();
    }

    /**
     * Check if .env file exists
     */
    public function exists(): bool
    {
        return file_exists($this->envFile);
    }

    /**
     * Get required keys that are missing
     */
    public function getMissing(array $required): array
    {
        $this->load();
        $missing = [];

        foreach ($required as $key) {
            if (empty($this->get($key))) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Load the .env file
     */
    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (! file_exists($this->envFile)) {
            return;
        }

        $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $this->vars[$key] = $value;
            }
        }
    }

    protected function getDefaultEnvFile(): string
    {
        return base_path('.env');
    }
}
