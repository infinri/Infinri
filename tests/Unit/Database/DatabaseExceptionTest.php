<?php declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
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
