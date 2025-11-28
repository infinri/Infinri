<?php

declare(strict_types=1);

namespace Tests\Unit\Indexer;

use App\Core\Indexer\AbstractIndexer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractIndexerTest extends TestCase
{
    private string $tempStatePath;
    private TestIndexer $indexer;

    protected function setUp(): void
    {
        $this->tempStatePath = sys_get_temp_dir() . '/indexer_test_' . uniqid() . '.php';
        $this->indexer = new TestIndexer($this->tempStatePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempStatePath)) {
            unlink($this->tempStatePath);
        }
    }

    #[Test]
    public function get_name_returns_indexer_name(): void
    {
        $this->assertSame('test', $this->indexer->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $this->assertSame('Test indexer', $this->indexer->getDescription());
    }

    #[Test]
    public function is_valid_returns_false_when_never_indexed(): void
    {
        $this->assertFalse($this->indexer->isValid());
    }

    #[Test]
    public function is_valid_returns_true_after_indexing(): void
    {
        $this->indexer->reindex();
        
        $this->assertTrue($this->indexer->isValid());
    }

    #[Test]
    public function get_last_indexed_at_returns_null_initially(): void
    {
        $this->assertNull($this->indexer->getLastIndexedAt());
    }

    #[Test]
    public function get_last_indexed_at_returns_datetime_after_indexing(): void
    {
        $this->indexer->reindex();
        
        $lastIndexed = $this->indexer->getLastIndexedAt();
        
        $this->assertInstanceOf(\DateTimeInterface::class, $lastIndexed);
    }

    #[Test]
    public function reindex_returns_count(): void
    {
        $count = $this->indexer->reindex();
        
        $this->assertSame(10, $count);
    }

    #[Test]
    public function reindex_partial_defaults_to_full_reindex(): void
    {
        $count = $this->indexer->reindexPartial([1, 2, 3]);
        
        $this->assertSame(10, $count);
    }

    #[Test]
    public function clear_resets_state(): void
    {
        $this->indexer->reindex();
        $this->assertTrue($this->indexer->isValid());
        
        $this->indexer->clear();
        
        $this->assertFalse($this->indexer->isValid());
    }

    #[Test]
    public function state_persists_to_file(): void
    {
        $this->indexer->reindex();
        
        // Create new instance to verify persistence
        $indexer2 = new TestIndexer($this->tempStatePath);
        
        $this->assertTrue($indexer2->isValid());
    }

}

/**
 * Concrete implementation for testing
 */
class TestIndexer extends AbstractIndexer
{
    protected string $name = 'test';
    protected string $description = 'Test indexer';

    public function __construct(string $statePath)
    {
        $this->statePath = $statePath;
    }

    public function reindex(): int
    {
        $count = 10;
        $this->markComplete($count);
        return $count;
    }
}
