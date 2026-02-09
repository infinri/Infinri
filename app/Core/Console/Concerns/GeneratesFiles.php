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
namespace App\Core\Console\Concerns;

/**
 * Shared helpers for commands that generate files/scaffolds
 */
trait GeneratesFiles
{
    /**
     * Require a positional argument or print usage and return null
     */
    protected function requireArgument(array $args, int $index, string $usage, string $example = ''): ?string
    {
        $value = $args[$index] ?? null;

        if ($value === null) {
            $this->error("Usage: {$usage}");
            if ($example !== '') {
                $this->line("  Example: {$example}");
            }
        }

        return $value;
    }

    /**
     * Ensure a path does not already exist (file or directory)
     *
     * @return bool true if path already exists (caller should abort)
     */
    protected function pathExists(string $path, string $label): bool
    {
        if (file_exists($path)) {
            $this->error("{$label} already exists.");

            return true;
        }

        return false;
    }

    /**
     * Write content to a file, ensuring the parent directory exists
     */
    protected function writeFile(string $filepath, string $content, string $displayPath = ''): void
    {
        ensure_directory(dirname($filepath));
        file_put_contents($filepath, $content);

        if ($displayPath !== '') {
            $this->line("  ✓ Created: {$displayPath}");
        }
    }

    /**
     * Convert a snake_case or kebab-case name to PascalCase class name
     */
    protected function toClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }
}
