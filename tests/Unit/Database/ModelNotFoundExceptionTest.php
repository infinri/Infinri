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

use App\Core\Database\ModelNotFoundException;
use App\Core\Database\DatabaseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModelNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function constructor_stores_model(): void
    {
        $exception = new ModelNotFoundException('App\\Models\\User', 1);
        
        $this->assertSame('App\\Models\\User', $exception->getModel());
    }

    #[Test]
    public function constructor_stores_id(): void
    {
        $exception = new ModelNotFoundException('App\\Models\\User', 42);
        
        $this->assertSame(42, $exception->getId());
    }

    #[Test]
    public function constructor_accepts_string_id(): void
    {
        $exception = new ModelNotFoundException('App\\Models\\User', 'uuid-123');
        
        $this->assertSame('uuid-123', $exception->getId());
    }

    #[Test]
    public function message_includes_model_and_id(): void
    {
        $exception = new ModelNotFoundException('User', 5);
        
        $this->assertStringContainsString('User', $exception->getMessage());
        $this->assertStringContainsString('5', $exception->getMessage());
    }

    #[Test]
    public function code_is_404(): void
    {
        $exception = new ModelNotFoundException('User', 1);
        
        $this->assertSame(404, $exception->getCode());
    }

    #[Test]
    public function extends_database_exception(): void
    {
        $exception = new ModelNotFoundException('User', 1);
        
        $this->assertInstanceOf(DatabaseException::class, $exception);
    }
}
