<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Core\Database\DatabaseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseExceptionTest extends TestCase
{
    #[Test]
    public function constructor_sets_message(): void
    {
        $exception = new DatabaseException('Database error');
        
        $this->assertSame('Database error', $exception->getMessage());
    }

    #[Test]
    public function constructor_sets_code(): void
    {
        $exception = new DatabaseException('Error', 500);
        
        $this->assertSame(500, $exception->getCode());
    }

    #[Test]
    public function constructor_sets_previous(): void
    {
        $previous = new \Exception('Previous');
        $exception = new DatabaseException('Error', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function extends_exception(): void
    {
        $exception = new DatabaseException('Error');
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
