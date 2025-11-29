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

/**
 * Has Many Relationship
 * 
 * Represents a one-to-many relationship where the foreign key is on the related models.
 */
class HasMany extends Relation
{
    /**
     * Get the results of the relationship
     */
    public function getResults(): array
    {
        $localValue = $this->parent->getAttribute($this->localKey);
        
        if ($localValue === null) {
            return [];
        }

        return $this->newQuery()
            ->where($this->foreignKey, $localValue)
            ->get();
    }

    /**
     * Create a new related model
     */
    public function create(array $attributes = []): Model
    {
        $attributes[$this->foreignKey] = $this->parent->getAttribute($this->localKey);
        
        return $this->related::create($attributes);
    }

    /**
     * Create multiple related models
     */
    public function createMany(array $records): array
    {
        $models = [];
        
        foreach ($records as $attributes) {
            $models[] = $this->create($attributes);
        }
        
        return $models;
    }

    /**
     * Save a related model
     */
    public function save(Model $model): Model
    {
        $model->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        $model->save();
        
        return $model;
    }

    /**
     * Save multiple related models
     */
    public function saveMany(array $models): array
    {
        foreach ($models as $model) {
            $this->save($model);
        }
        
        return $models;
    }
}
