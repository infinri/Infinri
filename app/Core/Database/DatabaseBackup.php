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
namespace App\Core\Database;

/**
 * Database Backup
 * 
 * Handles database backup operations using native dump utilities.
 * Supports PostgreSQL and MySQL.
 */
class DatabaseBackup
{
    protected array $config;
    protected string $backupDir;

    public function __construct(array $config, ?string $backupDir = null)
    {
        $this->config = $config;
        $this->backupDir = $backupDir ?? $this->getDefaultBackupDir();
    }

    /**
     * Create a backup of the database
     * 
     * @return array{success: bool, path: ?string, size: ?int, error: ?string}
     */
    public function backup(): array
    {
        $this->ensureBackupDir();

        $timestamp = date('Y-m-d_His');
        $backupFile = "{$this->backupDir}/backup_{$timestamp}.sql";

        $driver = $this->config['driver'] ?? 'pgsql';

        $cmd = match ($driver) {
            'pgsql' => $this->buildPgDumpCommand($backupFile),
            'mysql' => $this->buildMysqlDumpCommand($backupFile),
            default => null,
        };

        if ($cmd === null) {
            return [
                'success' => false,
                'path' => null,
                'size' => null,
                'error' => "Backup not supported for driver: {$driver}",
            ];
        }

        $output = [];
        $result = null;
        exec($cmd, $output, $result);

        if ($result === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            return [
                'success' => true,
                'path' => $backupFile,
                'size' => filesize($backupFile),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'path' => $backupFile,
            'size' => file_exists($backupFile) ? filesize($backupFile) : 0,
            'error' => 'Backup command failed or produced empty file',
        ];
    }

    /**
     * Restore from a backup file
     */
    public function restore(string $backupFile): array
    {
        if (!file_exists($backupFile)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        $driver = $this->config['driver'] ?? 'pgsql';

        $cmd = match ($driver) {
            'pgsql' => $this->buildPgRestoreCommand($backupFile),
            'mysql' => $this->buildMysqlRestoreCommand($backupFile),
            default => null,
        };

        if ($cmd === null) {
            return ['success' => false, 'error' => "Restore not supported for driver: {$driver}"];
        }

        $output = [];
        $result = null;
        exec($cmd, $output, $result);

        return [
            'success' => $result === 0,
            'error' => $result !== 0 ? 'Restore command failed' : null,
        ];
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $files = glob($this->backupDir . '/backup_*.sql');
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'path' => $file,
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => filemtime($file),
            ];
        }

        // Sort by newest first
        usort($backups, fn($a, $b) => $b['created'] <=> $a['created']);

        return $backups;
    }

    /**
     * Delete old backups, keeping the most recent N
     */
    public function pruneBackups(int $keep = 5): int
    {
        $backups = $this->listBackups();
        $deleted = 0;

        foreach (array_slice($backups, $keep) as $backup) {
            if (unlink($backup['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function buildPgDumpCommand(string $outputFile): string
    {
        return sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s > %s 2>/dev/null',
            escapeshellarg($this->config['password'] ?? ''),
            escapeshellarg($this->config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($this->config['port'] ?? 5432)),
            escapeshellarg($this->config['username'] ?? ''),
            escapeshellarg($this->config['database'] ?? ''),
            escapeshellarg($outputFile)
        );
    }

    protected function buildMysqlDumpCommand(string $outputFile): string
    {
        return sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>/dev/null',
            escapeshellarg($this->config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($this->config['port'] ?? 3306)),
            escapeshellarg($this->config['username'] ?? ''),
            escapeshellarg($this->config['password'] ?? ''),
            escapeshellarg($this->config['database'] ?? ''),
            escapeshellarg($outputFile)
        );
    }

    protected function buildPgRestoreCommand(string $inputFile): string
    {
        return sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s %s < %s 2>/dev/null',
            escapeshellarg($this->config['password'] ?? ''),
            escapeshellarg($this->config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($this->config['port'] ?? 5432)),
            escapeshellarg($this->config['username'] ?? ''),
            escapeshellarg($this->config['database'] ?? ''),
            escapeshellarg($inputFile)
        );
    }

    protected function buildMysqlRestoreCommand(string $inputFile): string
    {
        return sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>/dev/null',
            escapeshellarg($this->config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($this->config['port'] ?? 3306)),
            escapeshellarg($this->config['username'] ?? ''),
            escapeshellarg($this->config['password'] ?? ''),
            escapeshellarg($this->config['database'] ?? ''),
            escapeshellarg($inputFile)
        );
    }

    protected function ensureBackupDir(): void
    {
        ensure_directory($this->backupDir);
    }

    protected function getDefaultBackupDir(): string
    {
        return base_path('var/backups');
    }

    /**
     * Format bytes to human readable
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
