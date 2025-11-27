<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDOException;

/**
 * Query Exception
 * 
 * Thrown when a database query fails.
 */
class QueryException extends DatabaseException
{
    protected string $sql;
    protected array $bindings;

    public function __construct(string $sql, array $bindings, PDOException $previous)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;

        $message = $this->formatMessage($previous);
        
        parent::__construct($message, (int) $previous->getCode(), $previous);
    }

    protected function formatMessage(PDOException $previous): string
    {
        return sprintf(
            "Query failed: %s\nSQL: %s\nBindings: %s",
            $previous->getMessage(),
            $this->sql,
            json_encode($this->bindings)
        );
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
