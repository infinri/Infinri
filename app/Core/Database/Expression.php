<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Raw SQL Expression
 * 
 * Used to inject raw SQL into queries without escaping.
 */
class Expression
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
