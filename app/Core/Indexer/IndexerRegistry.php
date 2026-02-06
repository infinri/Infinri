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

/**
 * Indexer Registry
 *
 * Manages registered indexers from modules.
 * Modules register their indexers via service providers or module.php.
 */
class IndexerRegistry
{
    /**
     * Registered indexers
     *
     * @var array<string, IndexerInterface>
     */
    protected array $indexers = [];

    /**
     * Indexer classes (for lazy instantiation)
     *
     * @var array<string, string>
     */
    protected array $indexerClasses = [];

    /**
     * Register an indexer instance
     */
    public function register(IndexerInterface $indexer): void
    {
        $this->indexers[$indexer->getName()] = $indexer;
    }

    /**
     * Register an indexer class (lazy loading)
     */
    public function registerClass(string $name, string $class): void
    {
        $this->indexerClasses[$name] = $class;
    }

    /**
     * Get an indexer by name
     */
    public function get(string $name): ?IndexerInterface
    {
        // Check instances first
        if (isset($this->indexers[$name])) {
            return $this->indexers[$name];
        }

        // Try to instantiate from class
        if (isset($this->indexerClasses[$name])) {
            $class = $this->indexerClasses[$name];
            if (class_exists($class)) {
                $this->indexers[$name] = new $class();

                return $this->indexers[$name];
            }
        }

        return null;
    }

    /**
     * Check if indexer exists
     */
    public function has(string $name): bool
    {
        return isset($this->indexers[$name]) || isset($this->indexerClasses[$name]);
    }

    /**
     * Get all registered indexer names
     */
    public function getNames(): array
    {
        return array_unique(array_merge(
            array_keys($this->indexers),
            array_keys($this->indexerClasses)
        ));
    }

    /**
     * Get all indexers
     *
     * @return IndexerInterface[]
     */
    public function all(): array
    {
        // Instantiate all lazy-loaded indexers
        foreach ($this->indexerClasses as $name => $class) {
            if (! isset($this->indexers[$name]) && class_exists($class)) {
                $this->indexers[$name] = new $class();
            }
        }

        return $this->indexers;
    }

    /**
     * Get indexer metadata for listing
     */
    public function getMetadata(): array
    {
        $metadata = [];

        foreach ($this->all() as $name => $indexer) {
            $metadata[$name] = [
                'name' => $indexer->getName(),
                'description' => $indexer->getDescription(),
                'valid' => $indexer->isValid(),
                'lastIndexedAt' => $indexer->getLastIndexedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $metadata;
    }
}
