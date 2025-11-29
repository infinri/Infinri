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
namespace App\Core\Console\Commands;

use App\Core\Console\Command;

/**
 * Code Stats Command
 * 
 * Measures lines of code (LOC) and identifies large files.
 */
class CodeStatsCommand extends Command
{
    protected string $name = 'code:stats';
    protected string $description = 'Show code statistics (LOC, file counts)';
    protected array $aliases = ['loc'];

    protected array $stats = [];

    public function handle(array $args = []): int
    {
        $rootDir = $this->getRootDir();
        $verbose = in_array('--verbose', $args) || in_array('-v', $args);

        $this->line("Code Statistics");
        $this->line(str_repeat('═', 60));

        $dirs = [
            'app/Core' => 'Core Framework',
            'app/Http' => 'HTTP Layer',
            'app/Models' => 'Models',
            'app/modules' => 'Modules',
            'app/base' => 'Legacy Base',
            'tests' => 'Tests',
        ];

        $totalLoc = 0;
        $totalFiles = 0;

        foreach ($dirs as $dir => $label) {
            $path = $rootDir . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }

            $stats = $this->scanDirectory($path);
            $this->stats[$dir] = $stats;
            
            $totalLoc += $stats['loc'];
            $totalFiles += $stats['files'];

            $this->line(sprintf("\n📁 %s (%s)", $label, $dir));
            $this->line(sprintf(
                "   Files: %d | LOC: %s | Avg: %d lines/file",
                $stats['files'],
                number_format($stats['loc']),
                $stats['files'] > 0 ? round($stats['loc'] / $stats['files']) : 0
            ));

            if ($verbose && !empty($stats['largest'])) {
                $this->line("   Largest files:");
                foreach (array_slice($stats['largest'], 0, 3) as $file) {
                    $relativePath = str_replace($rootDir . '/', '', $file['path']);
                    $this->line("     • {$file['loc']} lines: {$relativePath}");
                }
            }
        }

        $this->line(str_repeat('─', 60));
        $this->line(sprintf("\n📊 Total: %d files, %s lines of code", $totalFiles, number_format($totalLoc)));

        $this->showLargestFiles($rootDir);

        $this->line();
        return 0;
    }

    protected function scanDirectory(string $dir): array
    {
        $loc = 0;
        $files = 0;
        $largest = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files++;
            $content = file_get_contents($file->getPathname());
            $lines = substr_count($content, "\n") + 1;
            $loc += $lines;

            $largest[] = ['path' => $file->getPathname(), 'loc' => $lines];
        }

        usort($largest, fn($a, $b) => $b['loc'] <=> $a['loc']);

        return [
            'loc' => $loc,
            'files' => $files,
            'largest' => array_slice($largest, 0, 10),
        ];
    }

    protected function showLargestFiles(string $rootDir): void
    {
        $allFiles = [];

        foreach ($this->stats as $stats) {
            foreach ($stats['largest'] as $file) {
                $allFiles[] = $file;
            }
        }

        usort($allFiles, fn($a, $b) => $b['loc'] <=> $a['loc']);

        $this->line("\n🔍 Largest Files (candidates for refactoring)");
        foreach (array_slice($allFiles, 0, 10) as $file) {
            $relativePath = str_replace($rootDir . '/', '', $file['path']);
            $status = $file['loc'] > 300 ? '⚠️ ' : '  ';
            $this->line(sprintf("%s %4d lines: %s", $status, $file['loc'], $relativePath));
        }
    }
}
