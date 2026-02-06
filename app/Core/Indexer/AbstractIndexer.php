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
namespace App\Core\Indexer;

use App\Core\Contracts\Indexer\IndexerInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Abstract Indexer
 *
 * Base class for module indexers. Provides common functionality.
 */
abstract class AbstractIndexer implements IndexerInterface
{
    /**
     * Indexer name
     */
    protected string $name;

    /**
     * Indexer description
     */
    protected string $description = '';

    /**
     * State file path
     */
    protected ?string $statePath = null;

    /**
     * Cached state
     */
    protected ?array $state = null;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return $this->getLastIndexedAt() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastIndexedAt(): ?DateTimeInterface
    {
        $state = $this->loadState();

        if (isset($state['lastIndexedAt'])) {
            return new DateTimeImmutable($state['lastIndexedAt']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reindexPartial(array $ids): int
    {
        // Default implementation: full reindex
        // Override in subclass for incremental support
        return $this->reindex();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->saveState([
            'lastIndexedAt' => null,
            'count' => 0,
        ]);
    }

    /**
     * Mark indexer as complete
     */
    protected function markComplete(int $count): void
    {
        $this->saveState([
            'lastIndexedAt' => new DateTimeImmutable()->format('c'),
            'count' => $count,
        ]);
    }

    /**
     * Get state file path
     */
    protected function getStatePath(): string
    {
        if ($this->statePath !== null) {
            return $this->statePath;
        }

        $basePath = function_exists('app')
            ? app()->basePath()
            : dirname(__DIR__, 3);

        return $basePath . '/var/state/indexer_' . $this->name . '.php';
    }

    /**
     * Load indexer state
     */
    protected function loadState(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $path = $this->getStatePath();

        if (file_exists($path)) {
            $this->state = require $path;

            return $this->state;
        }

        return $this->state = [];
    }

    /**
     * Save indexer state
     */
    protected function saveState(array $state): void
    {
        $this->state = $state;
        save_php_array($this->getStatePath(), $state, "Indexer State: {$this->name}");
    }
}
