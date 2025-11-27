<?php

declare(strict_types=1);

namespace App\Core\Contracts\Indexer;

/**
 * Indexer Interface
 * 
 * All module indexers must implement this interface.
 */
interface IndexerInterface
{
    /**
     * Get the indexer name (unique identifier)
     */
    public function getName(): string;

    /**
     * Get human-readable description
     */
    public function getDescription(): string;

    /**
     * Execute full reindex
     * 
     * @return int Number of items indexed
     */
    public function reindex(): int;

    /**
     * Execute partial/incremental reindex
     * 
     * @param array $ids Specific IDs to reindex
     * @return int Number of items indexed
     */
    public function reindexPartial(array $ids): int;

    /**
     * Clear the index
     */
    public function clear(): void;

    /**
     * Check if index is valid/current
     */
    public function isValid(): bool;

    /**
     * Get last indexed timestamp
     */
    public function getLastIndexedAt(): ?\DateTimeInterface;
}
