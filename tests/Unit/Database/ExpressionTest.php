<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Core\Database\Expression;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    #[Test]
    public function constructor_stores_value(): void
    {
        $expression = new Expression('NOW()');
        
        $this->assertSame('NOW()', $expression->getValue());
    }

    #[Test]
    public function get_value_returns_raw_sql(): void
    {
        $expression = new Expression('COUNT(*)');
        
        $this->assertSame('COUNT(*)', $expression->getValue());
    }

    #[Test]
    public function to_string_returns_value(): void
    {
        $expression = new Expression('NOW()');
        
        $this->assertSame('NOW()', (string) $expression);
    }

    #[Test]
    public function can_contain_complex_sql(): void
    {
        $sql = "CASE WHEN status = 'active' THEN 1 ELSE 0 END";
        $expression = new Expression($sql);
        
        $this->assertSame($sql, $expression->getValue());
    }
}
