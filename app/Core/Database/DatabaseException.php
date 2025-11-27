<?php

declare(strict_types=1);

namespace App\Core\Database;

use Exception;

/**
 * Database Exception
 * 
 * Thrown when a database connection or operation fails.
 */
class DatabaseException extends Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        
        $this->logException();
    }

    protected function logException(): void
    {
        if (function_exists('logger')) {
            try {
                logger()->error('Database exception', [
                    'message' => $this->getMessage(),
                    'code' => $this->getCode(),
                    'file' => $this->getFile(),
                    'line' => $this->getLine(),
                ]);
            } catch (\Throwable $e) {
                // Silently fail if logging not available
            }
        }
    }
}
