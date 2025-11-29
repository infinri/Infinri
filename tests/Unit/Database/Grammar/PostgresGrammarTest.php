<?php

declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Database\Grammar;

use App\Core\Database\Grammar\PostgresGrammar;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PostgresGrammarTest extends TestCase
{
    private PostgresGrammar $grammar;

    protected function setUp(): void
    {
        $this->grammar = new PostgresGrammar();
    }

    #[Test]
    public function compile_insert_generates_postgres_sql(): void
    {
        $sql = $this->grammar->compileInsert('users', ['name', 'email']);
        
        $this->assertStringContainsString('INSERT INTO "users"', $sql);
        $this->assertStringContainsString('"name"', $sql);
        $this->assertStringContainsString('"email"', $sql);
        $this->assertStringContainsString('RETURNING id', $sql);
    }

    #[Test]
    public function wrap_table_adds_quotes(): void
    {
        $result = $this->grammar->wrapTable('users');
        
        $this->assertSame('"users"', $result);
    }

    #[Test]
    public function wrap_column_adds_quotes(): void
    {
        $result = $this->grammar->wrapColumn('name');
        
        $this->assertSame('"name"', $result);
    }

    #[Test]
    public function wrap_column_preserves_asterisk(): void
    {
        $result = $this->grammar->wrapColumn('*');
        
        $this->assertSame('*', $result);
    }

    #[Test]
    public function wrap_column_handles_dot_notation(): void
    {
        $result = $this->grammar->wrapColumn('users.name');
        
        $this->assertSame('"users"."name"', $result);
    }
}
