<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Error\Concerns\LogsExceptions;
use Exception;

/**
 * Database Exception
 */
class DatabaseException extends Exception
{
    use LogsExceptions;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logException('error', 'Database exception', $this->getExceptionContext());
    }
}
