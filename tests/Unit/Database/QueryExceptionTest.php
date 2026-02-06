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

use App\Core\Database\QueryException;
use App\Core\Database\DatabaseException;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryExceptionTest extends TestCase
{
    #[Test]
    public function constructor_stores_sql(): void
    {
        $pdo = new PDOException('SQLSTATE error');
        $exception = new QueryException('SELECT * FROM users', [], $pdo);
        
        $this->assertSame('SELECT * FROM users', $exception->getSql());
    }

    #[Test]
    public function constructor_stores_bindings(): void
    {
        $pdo = new PDOException('Error');
        $bindings = ['id' => 1, 'name' => 'test'];
        $exception = new QueryException('SELECT * FROM users WHERE id = ?', $bindings, $pdo);
        
        $this->assertSame($bindings, $exception->getBindings());
    }

    #[Test]
    public function message_includes_sql_and_bindings(): void
    {
        $pdo = new PDOException('Connection refused');
        $exception = new QueryException('SELECT * FROM users', ['id' => 1], $pdo);
        
        $this->assertStringContainsString('Connection refused', $exception->getMessage());
        $this->assertStringContainsString('SELECT * FROM users', $exception->getMessage());
    }

    #[Test]
    public function extends_database_exception(): void
    {
        $pdo = new PDOException('Error');
        $exception = new QueryException('SELECT 1', [], $pdo);
        
        $this->assertInstanceOf(DatabaseException::class, $exception);
    }

    #[Test]
    public function previous_is_pdo_exception(): void
    {
        $pdo = new PDOException('Error');
        $exception = new QueryException('SELECT 1', [], $pdo);
        
        $this->assertSame($pdo, $exception->getPrevious());
    }
}
