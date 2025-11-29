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
namespace App\Core\Database\Relations;

use App\Core\Database\Model;
use App\Core\Database\ModelQueryBuilder;

/**
 * Base Relation
 * 
 * Base class for all model relationships.
 */
abstract class Relation
{
    protected Model $parent;
    protected Model $related;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Get the results of the relationship
     */
    abstract public function getResults(): mixed;

    /**
     * Get a new query builder for the related model
     */
    protected function newQuery(): ModelQueryBuilder
    {
        return $this->related::query();
    }

    /**
     * Get the parent model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Get the related model
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * Get the foreign key
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }
}
