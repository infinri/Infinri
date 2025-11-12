<?php
declare(strict_types=1);
/**
 * Logger Helper
 *
 * Simple file-based logging with severity levels
 *
 * @package App\Helpers
 */

namespace App\Base\Helpers;

final class Logger
{
    private static string $logDir = '';

    /**
     * Initialize logger
     *
     * @return void
     */
    private static function init(): void
    {
        if (self::$logDir) {
            return;
        }

        self::$logDir = dirname(__DIR__, 3) . '/var/logs';

        if (! is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Write log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    private static function log(string $level, string $message, array $context): void
    {
        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? ' ' . json_encode($context) : '';
        $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";

        $logFile = self::$logDir . '/app-' . date('Y-m-d') . '.log';

        // Append to log file
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Rotate if too large (> 10MB)
        if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
            rename($logFile, $logFile . '.' . time());
        }
    }
}
