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
namespace Tests\Unit\Database\Grammar;

use App\Core\Database\Grammar\Grammar;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GrammarTest extends TestCase
{
    private Grammar $grammar;

    protected function setUp(): void
    {
        $this->grammar = new Grammar();
    }

    #[Test]
    public function compile_select_basic(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertSame('SELECT * FROM users', $sql);
    }

    #[Test]
    public function compile_select_with_columns(): void
    {
        $query = [
            'columns' => ['id', 'name', 'email'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertSame('SELECT id, name, email FROM users', $sql);
    }

    #[Test]
    public function compile_select_with_where(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'boolean' => 'AND'],
            ],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('WHERE id = ?', $sql);
    }

    #[Test]
    public function compile_select_with_order(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [
                ['column' => 'name', 'direction' => 'ASC'],
            ],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('ORDER BY name ASC', $sql);
    }

    #[Test]
    public function compile_select_with_limit(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => 10,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    #[Test]
    public function compile_select_with_offset(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => 10,
            'offset' => 20,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('OFFSET 20', $sql);
    }

    #[Test]
    public function compile_select_with_join(): void
    {
        $query = [
            'columns' => ['*'],
            'table' => 'users',
            'joins' => [
                ['type' => 'INNER', 'table' => 'posts', 'first' => 'users.id', 'operator' => '=', 'second' => 'posts.user_id'],
            ],
            'wheres' => [],
            'groups' => [],
            'havings' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('INNER JOIN posts ON users.id = posts.user_id', $sql);
    }

    #[Test]
    public function compile_select_with_group(): void
    {
        $query = [
            'columns' => ['status', 'COUNT(*) as count'],
            'table' => 'users',
            'joins' => [],
            'wheres' => [],
            'groups' => ['status'],
            'havings' => [],
            'orders' => [],
            'limit' => null,
            'offset' => null,
        ];
        
        $sql = $this->grammar->compileSelect($query);
        
        $this->assertStringContainsString('GROUP BY status', $sql);
    }

    #[Test]
    public function compile_wheres_with_in(): void
    {
        $wheres = [
            ['type' => 'in', 'column' => 'id', 'values' => [1, 2, 3], 'not' => false, 'boolean' => 'AND'],
        ];
        
        $sql = $this->grammar->compileWheres($wheres);
        
        $this->assertStringContainsString('id IN (?, ?, ?)', $sql);
    }

    #[Test]
    public function compile_wheres_with_not_in(): void
    {
        $wheres = [
            ['type' => 'in', 'column' => 'id', 'values' => [1, 2], 'not' => true, 'boolean' => 'AND'],
        ];
        
        $sql = $this->grammar->compileWheres($wheres);
        
        $this->assertStringContainsString('id NOT IN', $sql);
    }

    #[Test]
    public function compile_wheres_with_null(): void
    {
        $wheres = [
            ['type' => 'null', 'column' => 'deleted_at', 'not' => false, 'boolean' => 'AND'],
        ];
        
        $sql = $this->grammar->compileWheres($wheres);
        
        $this->assertStringContainsString('deleted_at IS NULL', $sql);
    }

    #[Test]
    public function compile_wheres_with_between(): void
    {
        $wheres = [
            ['type' => 'between', 'column' => 'age', 'boolean' => 'AND'],
        ];
        
        $sql = $this->grammar->compileWheres($wheres);
        
        $this->assertStringContainsString('age BETWEEN ? AND ?', $sql);
    }

    #[Test]
    public function compile_insert(): void
    {
        $sql = $this->grammar->compileInsert('users', ['name', 'email']);
        
        $this->assertStringContainsString('INSERT INTO users', $sql);
        $this->assertStringContainsString('(name, email)', $sql);
        $this->assertStringContainsString('VALUES (?, ?)', $sql);
    }

    #[Test]
    public function compile_update(): void
    {
        $sql = $this->grammar->compileUpdate('users', ['name', 'email'], ' WHERE id = ?');
        
        $this->assertStringContainsString('UPDATE users SET', $sql);
        $this->assertStringContainsString('name = ?', $sql);
        $this->assertStringContainsString('email = ?', $sql);
        $this->assertStringContainsString('WHERE id = ?', $sql);
    }

    #[Test]
    public function compile_delete(): void
    {
        $sql = $this->grammar->compileDelete('users', ' WHERE id = ?');
        
        $this->assertSame('DELETE FROM users WHERE id = ?', $sql);
    }

    #[Test]
    public function compile_wheres_with_raw(): void
    {
        $wheres = [
            [
                'type' => 'raw',
                'sql' => 'status = 1 AND deleted_at IS NULL',
                'boolean' => 'AND',
            ]
        ];
        
        $result = $this->grammar->compileWheres($wheres);
        
        $this->assertStringContainsString('status = 1 AND deleted_at IS NULL', $result);
    }
}
