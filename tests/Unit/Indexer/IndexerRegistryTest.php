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
namespace Tests\Unit\Indexer;

use App\Core\Indexer\IndexerRegistry;
use App\Core\Contracts\Indexer\IndexerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IndexerRegistryTest extends TestCase
{
    private IndexerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new IndexerRegistry();
    }

    #[Test]
    public function register_adds_indexer(): void
    {
        $indexer = $this->createMockIndexer('test');
        
        $this->registry->register($indexer);
        
        $this->assertTrue($this->registry->has('test'));
    }

    #[Test]
    public function register_class_adds_class_for_lazy_loading(): void
    {
        $this->registry->registerClass('lazy', DummyIndexer::class);
        
        $this->assertTrue($this->registry->has('lazy'));
    }

    #[Test]
    public function get_returns_registered_indexer(): void
    {
        $indexer = $this->createMockIndexer('test');
        $this->registry->register($indexer);
        
        $result = $this->registry->get('test');
        
        $this->assertSame($indexer, $result);
    }

    #[Test]
    public function get_returns_null_for_unknown_indexer(): void
    {
        $result = $this->registry->get('unknown');
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_instantiates_lazy_loaded_class(): void
    {
        $this->registry->registerClass('dummy', DummyIndexer::class);
        
        $result = $this->registry->get('dummy');
        
        $this->assertInstanceOf(DummyIndexer::class, $result);
    }

    #[Test]
    public function has_returns_true_for_registered_indexer(): void
    {
        $indexer = $this->createMockIndexer('test');
        $this->registry->register($indexer);
        
        $this->assertTrue($this->registry->has('test'));
    }

    #[Test]
    public function has_returns_true_for_registered_class(): void
    {
        $this->registry->registerClass('lazy', DummyIndexer::class);
        
        $this->assertTrue($this->registry->has('lazy'));
    }

    #[Test]
    public function has_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->registry->has('unknown'));
    }

    #[Test]
    public function get_names_returns_all_names(): void
    {
        $indexer = $this->createMockIndexer('instance');
        $this->registry->register($indexer);
        $this->registry->registerClass('class', DummyIndexer::class);
        
        $names = $this->registry->getNames();
        
        $this->assertContains('instance', $names);
        $this->assertContains('class', $names);
    }

    #[Test]
    public function all_returns_all_indexers(): void
    {
        $indexer = $this->createMockIndexer('test');
        $this->registry->register($indexer);
        $this->registry->registerClass('dummy', DummyIndexer::class);
        
        $all = $this->registry->all();
        
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('test', $all);
        $this->assertArrayHasKey('dummy', $all);
    }

    #[Test]
    public function get_metadata_returns_indexer_info(): void
    {
        $indexer = $this->createMockIndexer('test', 'Test description');
        $this->registry->register($indexer);
        
        $metadata = $this->registry->getMetadata();
        
        $this->assertArrayHasKey('test', $metadata);
        $this->assertSame('test', $metadata['test']['name']);
        $this->assertSame('Test description', $metadata['test']['description']);
    }

    private function createMockIndexer(string $name, string $description = ''): IndexerInterface
    {
        $mock = $this->createMock(IndexerInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getDescription')->willReturn($description);
        $mock->method('isValid')->willReturn(false);
        $mock->method('getLastIndexedAt')->willReturn(null);
        return $mock;
    }
}

/**
 * Dummy indexer for testing lazy loading
 */
class DummyIndexer implements IndexerInterface
{
    public function getName(): string { return 'dummy'; }
    public function getDescription(): string { return 'Dummy indexer'; }
    public function reindex(): int { return 0; }
    public function reindexPartial(array $ids): int { return 0; }
    public function isValid(): bool { return false; }
    public function getLastIndexedAt(): ?\DateTimeInterface { return null; }
    public function clear(): void {}
}
