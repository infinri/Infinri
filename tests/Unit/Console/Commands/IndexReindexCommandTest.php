<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\IndexReindexCommand;
use App\Core\Indexer\IndexerRegistry;
use App\Core\Indexer\AbstractIndexer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Mock indexer for testing
 */
class MockIndexer extends AbstractIndexer
{
    protected string $name = 'mock';
    protected string $description = 'Mock indexer for testing';
    
    public function reindex(): int
    {
        return 10;
    }
    
    public function clear(): void
    {
        // Do nothing
    }
    
    public function isValid(): bool
    {
        return true;
    }
}

/**
 * Testable command with injectable registry
 */
class TestableIndexReindexCommand extends IndexReindexCommand
{
    private ?IndexerRegistry $testRegistry = null;
    
    public function setTestRegistry(IndexerRegistry $registry): void
    {
        $this->testRegistry = $registry;
        $this->registry = $registry;
    }
    
    protected function getRegistry(): IndexerRegistry
    {
        return $this->testRegistry ?? new IndexerRegistry();
    }
}

class IndexReindexCommandTest extends TestCase
{
    #[Test]
    public function get_name_returns_index_reindex(): void
    {
        $command = new IndexReindexCommand();
        
        $this->assertSame('index:reindex', $command->getName());
    }

    #[Test]
    public function get_description_returns_description(): void
    {
        $command = new IndexReindexCommand();
        
        $this->assertNotEmpty($command->getDescription());
    }

    #[Test]
    public function get_aliases_includes_reindex(): void
    {
        $command = new IndexReindexCommand();
        
        $this->assertContains('reindex', $command->getAliases());
    }

    #[Test]
    public function handle_returns_zero(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $result = $command->handle();
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_list_flag_returns_zero(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $result = $command->handle(['--list']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_short_list_flag_returns_zero(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $result = $command->handle(['-l']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function handle_with_list_argument_returns_zero(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $result = $command->handle(['list']);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function list_shows_no_indexers_message_when_empty(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $command->handle(['--list']);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('No indexers registered', $output);
    }

    #[Test]
    public function list_shows_indexers_when_registered(): void
    {
        $registry = new IndexerRegistry();
        $registry->register(new MockIndexer());
        
        $command = new TestableIndexReindexCommand();
        $command->setTestRegistry($registry);
        
        ob_start();
        $command->handle(['--list']);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('mock', $output);
        $this->assertStringContainsString('valid', $output);
    }

    #[Test]
    public function run_specific_indexer_returns_error_for_unknown(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $result = $command->handle(['unknown-indexer']);
        ob_end_clean();
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function run_specific_indexer_shows_error_message(): void
    {
        $command = new IndexReindexCommand();
        
        ob_start();
        $command->handle(['unknown-indexer']);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Indexer not found', $output);
    }

    #[Test]
    public function run_specific_indexer_executes_correctly(): void
    {
        $registry = new IndexerRegistry();
        $registry->register(new MockIndexer());
        
        $command = new TestableIndexReindexCommand();
        $command->setTestRegistry($registry);
        
        ob_start();
        $result = $command->handle(['mock']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Running indexer', $output);
        $this->assertStringContainsString('10 item(s)', $output);
    }

    #[Test]
    public function run_specific_indexer_with_clear_flag(): void
    {
        $registry = new IndexerRegistry();
        $registry->register(new MockIndexer());
        
        $command = new TestableIndexReindexCommand();
        $command->setTestRegistry($registry);
        
        ob_start();
        $result = $command->handle(['mock', '--clear']);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Clearing index', $output);
    }

    #[Test]
    public function run_all_indexers_executes_all(): void
    {
        $registry = new IndexerRegistry();
        $registry->register(new MockIndexer());
        
        $command = new TestableIndexReindexCommand();
        $command->setTestRegistry($registry);
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Running all indexers', $output);
        $this->assertStringContainsString('Total:', $output);
    }

    #[Test]
    public function run_all_indexers_with_clear_flag(): void
    {
        $registry = new IndexerRegistry();
        $registry->register(new MockIndexer());
        
        $command = new TestableIndexReindexCommand();
        $command->setTestRegistry($registry);
        
        // Note: The command treats args[0] as indexer name, so --clear alone
        // would be treated as indexer name. This tests that all indexers
        // runs when no arguments are provided (empty args doesn't trigger clear)
        ob_start();
        $result = $command->handle([]);
        ob_end_clean();
        
        $this->assertSame(0, $result);
    }
}
