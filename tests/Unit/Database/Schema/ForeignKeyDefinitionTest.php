<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use App\Core\Database\Schema\ForeignKeyDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ForeignKeyDefinitionTest extends TestCase
{
    #[Test]
    public function it_creates_foreign_key_definition(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        
        $this->assertSame('user_id', $fk->getColumn());
    }

    #[Test]
    public function it_sets_references(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->references('id');
        
        $this->assertSame($fk, $result);
        $this->assertSame('id', $fk->getReferencedColumn());
    }

    #[Test]
    public function it_sets_on_table(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->on('users');
        
        $this->assertSame($fk, $result);
        $this->assertSame('users', $fk->getReferencedTable());
    }

    #[Test]
    public function it_sets_on_delete(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->onDelete('CASCADE');
        
        $this->assertSame($fk, $result);
        $this->assertSame('CASCADE', $fk->getOnDelete());
    }

    #[Test]
    public function it_sets_on_update(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->onUpdate('SET NULL');
        
        $this->assertSame($fk, $result);
        $this->assertSame('SET NULL', $fk->getOnUpdate());
    }

    #[Test]
    public function it_sets_cascade_on_delete(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->cascadeOnDelete();
        
        $this->assertSame($fk, $result);
        $this->assertSame('CASCADE', $fk->getOnDelete());
    }

    #[Test]
    public function it_sets_null_on_delete(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->nullOnDelete();
        
        $this->assertSame($fk, $result);
        $this->assertSame('SET NULL', $fk->getOnDelete());
    }

    #[Test]
    public function it_sets_restrict_on_delete(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->restrictOnDelete();
        
        $this->assertSame($fk, $result);
        $this->assertSame('RESTRICT', $fk->getOnDelete());
    }

    #[Test]
    public function it_sets_constraint_name(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        $result = $fk->name('fk_posts_user_id');
        
        $this->assertSame($fk, $result);
        $this->assertSame('fk_posts_user_id', $fk->getName());
    }

    #[Test]
    public function it_chains_all_methods(): void
    {
        $fk = new ForeignKeyDefinition('user_id');
        
        $fk->references('id')
           ->on('users')
           ->onDelete('CASCADE')
           ->onUpdate('RESTRICT')
           ->name('fk_posts_user');
        
        $this->assertSame('user_id', $fk->getColumn());
        $this->assertSame('id', $fk->getReferencedColumn());
        $this->assertSame('users', $fk->getReferencedTable());
        $this->assertSame('CASCADE', $fk->getOnDelete());
        $this->assertSame('RESTRICT', $fk->getOnUpdate());
        $this->assertSame('fk_posts_user', $fk->getName());
    }
}
