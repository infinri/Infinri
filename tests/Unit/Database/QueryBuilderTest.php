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
namespace Tests\Unit\Database;

use App\Core\Database\QueryBuilder;
use App\Core\Database\Connection;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private QueryBuilder $builder;
    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->method('getTablePrefix')->willReturn('');
        
        $this->builder = new QueryBuilder($this->connection);
    }

    #[Test]
    public function it_builds_simple_select(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['id', 'name', 'email'])
            ->toSql();

        $this->assertStringContainsString('SELECT id, name, email', $sql);
        $this->assertStringContainsString('FROM users', $sql);
    }

    #[Test]
    public function it_builds_select_all(): void
    {
        $sql = $this->builder
            ->table('users')
            ->toSql();

        $this->assertStringContainsString('SELECT *', $sql);
        $this->assertStringContainsString('FROM users', $sql);
    }

    #[Test]
    public function it_builds_where_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->where('id', '=', 1)
            ->toSql();

        $this->assertStringContainsString('WHERE id = ?', $sql);
        $this->assertEquals([1], $this->builder->getBindings());
    }

    #[Test]
    public function it_builds_where_with_shorthand(): void
    {
        $sql = $this->builder
            ->table('users')
            ->where('id', 1)
            ->toSql();

        $this->assertStringContainsString('WHERE id = ?', $sql);
    }

    #[Test]
    public function it_builds_multiple_where_clauses(): void
    {
        $sql = $this->builder
            ->table('users')
            ->where('id', 1)
            ->where('status', 'active')
            ->toSql();

        $this->assertStringContainsString('WHERE id = ?', $sql);
        $this->assertStringContainsString('AND status = ?', $sql);
    }

    #[Test]
    public function it_builds_or_where_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->where('id', 1)
            ->orWhere('id', 2)
            ->toSql();

        $this->assertStringContainsString('WHERE id = ?', $sql);
        $this->assertStringContainsString('OR id = ?', $sql);
    }

    #[Test]
    public function it_builds_where_in_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->whereIn('id', [1, 2, 3])
            ->toSql();

        $this->assertStringContainsString('WHERE id IN (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $this->builder->getBindings());
    }

    #[Test]
    public function it_builds_where_not_in_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->whereNotIn('id', [1, 2])
            ->toSql();

        $this->assertStringContainsString('WHERE id NOT IN (?, ?)', $sql);
    }

    #[Test]
    public function it_builds_where_null_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->whereNull('deleted_at')
            ->toSql();

        $this->assertStringContainsString('WHERE deleted_at IS NULL', $sql);
    }

    #[Test]
    public function it_builds_where_not_null_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->whereNotNull('email_verified_at')
            ->toSql();

        $this->assertStringContainsString('WHERE email_verified_at IS NOT NULL', $sql);
    }

    #[Test]
    public function it_builds_where_between_clause(): void
    {
        $sql = $this->builder
            ->table('orders')
            ->whereBetween('amount', [100, 500])
            ->toSql();

        $this->assertStringContainsString('WHERE amount BETWEEN ? AND ?', $sql);
        $this->assertEquals([100, 500], $this->builder->getBindings());
    }

    #[Test]
    public function it_builds_join_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->toSql();

        $this->assertStringContainsString('INNER JOIN posts ON users.id = posts.user_id', $sql);
    }

    #[Test]
    public function it_builds_left_join_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->toSql();

        $this->assertStringContainsString('LEFT JOIN posts ON users.id = posts.user_id', $sql);
    }

    #[Test]
    public function it_builds_right_join_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
            ->toSql();

        $this->assertStringContainsString('RIGHT JOIN posts ON users.id = posts.user_id', $sql);
    }

    #[Test]
    public function it_builds_order_by_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->orderBy('created_at', 'desc')
            ->toSql();

        $this->assertStringContainsString('ORDER BY created_at DESC', $sql);
    }

    #[Test]
    public function it_builds_multiple_order_by_clauses(): void
    {
        $sql = $this->builder
            ->table('users')
            ->orderBy('name')
            ->orderBy('created_at', 'desc')
            ->toSql();

        $this->assertStringContainsString('ORDER BY name ASC, created_at DESC', $sql);
    }

    #[Test]
    public function it_builds_group_by_clause(): void
    {
        $sql = $this->builder
            ->table('orders')
            ->select(['user_id', 'COUNT(*) as count'])
            ->groupBy('user_id')
            ->toSql();

        $this->assertStringContainsString('GROUP BY user_id', $sql);
    }

    #[Test]
    public function it_builds_having_clause(): void
    {
        $sql = $this->builder
            ->table('orders')
            ->groupBy('user_id')
            ->having('COUNT(*)', '>', 5)
            ->toSql();

        $this->assertStringContainsString('HAVING COUNT(*) > ?', $sql);
    }

    #[Test]
    public function it_builds_limit_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->limit(10)
            ->toSql();

        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    #[Test]
    public function it_builds_offset_clause(): void
    {
        $sql = $this->builder
            ->table('users')
            ->limit(10)
            ->offset(20)
            ->toSql();

        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertStringContainsString('OFFSET 20', $sql);
    }

    #[Test]
    public function it_builds_complex_query(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['users.id', 'users.name', 'posts.title'])
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->where('users.status', 'active')
            ->whereNotNull('posts.published_at')
            ->orderBy('posts.created_at', 'desc')
            ->limit(10)
            ->toSql();

        $this->assertStringContainsString('SELECT users.id, users.name, posts.title', $sql);
        $this->assertStringContainsString('FROM users', $sql);
        $this->assertStringContainsString('LEFT JOIN posts ON users.id = posts.user_id', $sql);
        $this->assertStringContainsString('WHERE users.status = ?', $sql);
        $this->assertStringContainsString('posts.published_at IS NOT NULL', $sql);
        $this->assertStringContainsString('ORDER BY posts.created_at DESC', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    #[Test]
    public function it_can_be_reset(): void
    {
        $this->builder
            ->table('users')
            ->where('id', 1)
            ->orderBy('name')
            ->limit(10);

        $this->builder->reset();

        $sql = $this->builder->table('posts')->toSql();

        $this->assertStringContainsString('SELECT *', $sql);
        $this->assertStringContainsString('FROM posts', $sql);
        $this->assertStringNotContainsString('WHERE', $sql);
        $this->assertStringNotContainsString('ORDER BY', $sql);
        $this->assertStringNotContainsString('LIMIT', $sql);
    }

    #[Test]
    public function it_can_be_cloned(): void
    {
        $this->builder
            ->table('users')
            ->where('status', 'active');

        $clone = $this->builder->clone();
        $clone->where('role', 'admin');

        $originalSql = $this->builder->toSql();
        $cloneSql = $clone->toSql();

        $this->assertStringNotContainsString('role', $originalSql);
        $this->assertStringContainsString('role = ?', $cloneSql);
    }

    #[Test]
    public function it_applies_table_prefix(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTablePrefix')->willReturn('app_');
        
        $builder = new QueryBuilder($connection);
        $sql = $builder->table('users')->toSql();

        $this->assertStringContainsString('FROM app_users', $sql);
    }

    #[Test]
    public function increment_calls_update_with_expression(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTablePrefix')->willReturn('');
        $connection->expects($this->once())
            ->method('update')
            ->willReturn(1);
        
        $builder = new QueryBuilder($connection);
        $result = $builder->table('counters')->where('id', 1)->increment('views', 1);
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function decrement_calls_update_with_expression(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getTablePrefix')->willReturn('');
        $connection->expects($this->once())
            ->method('update')
            ->willReturn(1);
        
        $builder = new QueryBuilder($connection);
        $result = $builder->table('counters')->where('id', 1)->decrement('views', 1);
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function insert_many_returns_zero_for_empty_array(): void
    {
        $builder = new QueryBuilder($this->connection);
        $result = $builder->table('users')->insertMany([]);
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function where_raw_adds_raw_clause(): void
    {
        $builder = new QueryBuilder($this->connection);
        $result = $builder->table('users')->whereRaw('status = ?', ['active']);
        
        $this->assertSame($builder, $result);
    }
}
