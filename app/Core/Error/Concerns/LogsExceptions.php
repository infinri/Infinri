<?php

declare(strict_types=1);

namespace App\Core\Error\Concerns;

/**
 * Trait for exceptions that log themselves
 */
trait LogsExceptions
{
    /**
     * Log this exception with the given level and context
     */
    protected function logException(string $level, string $message, array $context = []): void
    {
        if (!function_exists('logger')) {
            return;
        }

        try {
            logger()->$level($message, $context);
        } catch (\Throwable) {
            // Silently fail if logging not available
        }
    }

    /**
     * Get standard exception context
     */
    protected function getExceptionContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
