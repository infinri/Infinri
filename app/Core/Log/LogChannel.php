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
namespace App\Core\Log;

/**
 * Log Channel
 *
 * Represents a single log channel with its own file and rotation settings.
 */
class LogChannel
{
    protected string $name;
    protected string $path;
    protected int $maxSize;
    protected int $maxFiles;
    protected ?string $firstEntryDate = null;
    protected ?string $lastEntryDate = null;

    /**
     * @param string $name Channel name
     * @param string $path Full path to log file
     * @param int $maxSize Max file size in bytes before rotation (default 10MB)
     * @param int $maxFiles Max number of archived files to keep
     */
    public function __construct(
        string $name,
        string $path,
        int $maxSize = 10485760,
        int $maxFiles = 30
    ) {
        $this->name = $name;
        $this->path = $path;
        $this->maxSize = $maxSize;
        $this->maxFiles = $maxFiles;

        $this->ensureDirectoryExists();
        $this->loadMetadata();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Write entry to this channel's log file
     */
    public function write(string $entry): void
    {
        $this->checkRotation();

        // Track first entry date
        if ($this->firstEntryDate === null) {
            $this->firstEntryDate = date('Y-m-d_H-i-s');
            $this->saveMetadata();
        }

        // Update last entry date
        $this->lastEntryDate = date('Y-m-d_H-i-s');

        file_put_contents($this->path, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if rotation is needed and perform it
     */
    protected function checkRotation(): void
    {
        if (! file_exists($this->path)) {
            return;
        }

        if (filesize($this->path) >= $this->maxSize) {
            $this->rotate();
        }
    }

    /**
     * Rotate the log file
     */
    public function rotate(): void
    {
        if (! file_exists($this->path) || filesize($this->path) === 0) {
            return;
        }

        $archiveDir = dirname($this->path) . '/archive';
        if (! is_dir($archiveDir)) {
            mkdir($archiveDir, 0o755, true);
        }

        // Build archive filename with date range
        $firstDate = $this->firstEntryDate ?? date('Y-m-d_H-i-s');
        $lastDate = $this->lastEntryDate ?? date('Y-m-d_H-i-s');
        $archiveName = sprintf(
            '%s_%s_to_%s.log',
            $this->name,
            $firstDate,
            $lastDate
        );

        $archivePath = $archiveDir . '/' . $archiveName;
        $zipPath = $archivePath . '.gz';

        // Compress and archive
        $this->compressFile($this->path, $zipPath);

        // Clear current log file
        file_put_contents($this->path, '');

        // Reset date tracking
        $this->firstEntryDate = null;
        $this->lastEntryDate = null;
        $this->saveMetadata();

        // Cleanup old archives
        $this->cleanupOldArchives($archiveDir);
    }

    /**
     * Compress a file using gzip
     */
    protected function compressFile(string $source, string $destination): void
    {
        $data = file_get_contents($source);
        $compressed = gzencode($data, 9);
        file_put_contents($destination, $compressed);
    }

    /**
     * Remove old archive files beyond maxFiles limit
     */
    protected function cleanupOldArchives(string $archiveDir): void
    {
        $pattern = $archiveDir . '/' . $this->name . '_*.gz';
        $files = glob($pattern);

        if ($files === false || count($files) <= $this->maxFiles) {
            return;
        }

        // Sort by modification time (oldest first)
        usort($files, fn ($a, $b) => filemtime($a) - filemtime($b));

        // Remove oldest files
        $toRemove = count($files) - $this->maxFiles;
        for ($i = 0; $i < $toRemove; $i++) {
            unlink($files[$i]);
        }
    }

    /**
     * Ensure log directory exists
     */
    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);
        if (! is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    /**
     * Load metadata about current log file
     */
    protected function loadMetadata(): void
    {
        $metaPath = $this->path . '.meta';
        if (file_exists($metaPath)) {
            $data = json_decode(file_get_contents($metaPath), true);
            $this->firstEntryDate = $data['first_entry'] ?? null;
            $this->lastEntryDate = $data['last_entry'] ?? null;
        }
    }

    /**
     * Save metadata about current log file
     */
    protected function saveMetadata(): void
    {
        $metaPath = $this->path . '.meta';
        $data = [
            'first_entry' => $this->firstEntryDate,
            'last_entry' => $this->lastEntryDate,
        ];
        file_put_contents($metaPath, json_encode($data));
    }
}
