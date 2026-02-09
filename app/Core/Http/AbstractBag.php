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
namespace App\Core\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Abstract Bag
 *
 * Base class for parameter and header bag containers.
 * Provides shared IteratorAggregate/Countable boilerplate
 * and common accessor methods.
 */
abstract class AbstractBag implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    protected array $items = [];

    /**
     * Get all items
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get item keys
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Get iterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return count($this->items);
    }
}
