<?php

declare(strict_types=1);

namespace App\Core\Log;

use App\Core\Contracts\Log\LoggerInterface;

/**
 * Logger
 * 
 * Outputs structured JSON logs with correlation IDs
 */
class Logger implements LoggerInterface
{
    /**
     * The path to the log file
     *
     * @var string
     */
    protected string $logPath;

    /**
     * The current correlation ID
     *
     * @var string|null
     */
    protected ?string $correlationId = null;

    /**
     * Additional context to include in all log entries
     *
     * @var array
     */
    protected array $globalContext = [];

    /**
     * Create a new logger instance
     *
     * @param string $logPath
     */
    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        $this->ensureLogDirectoryExists();
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $entry = $this->formatEntry($level, $message, $context);
        
        $this->writeToFile($entry);
    }

    /**
     * Format a log entry as JSON
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatEntry(string $level, string $message, array $context): string
    {
        $entry = [
            'correlation_id' => $this->getCorrelationId(),
            'timestamp' => date('Y-m-d\TH:i:s.uP'),
            'level' => $level,
            'message' => $message,
            'context' => array_merge($this->globalContext, $context),
            'memory' => memory_get_usage(true),
        ];

        return json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    /**
     * Write entry to log file
     *
     * @param string $entry
     * @return void
     */
    protected function writeToFile(string $entry): void
    {
        file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Ensure the log directory exists
     *
     * @return void
     */
    protected function ensureLogDirectoryExists(): void
    {
        $directory = dirname($this->logPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Set the correlation ID for this request
     *
     * @param string $id
     * @return void
     */
    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    /**
     * Get the correlation ID
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        if ($this->correlationId === null) {
            $this->correlationId = $this->generateCorrelationId();
        }

        return $this->correlationId;
    }

    /**
     * Generate a new correlation ID
     *
     * @return string
     */
    protected function generateCorrelationId(): string
    {
        return 'req_' . bin2hex(random_bytes(6));
    }

    /**
     * Set global context to include in all log entries
     *
     * @param array $context
     * @return void
     */
    public function setGlobalContext(array $context): void
    {
        $this->globalContext = $context;
    }

    /**
     * Add to global context
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addGlobalContext(string $key, mixed $value): void
    {
        $this->globalContext[$key] = $value;
    }
}
