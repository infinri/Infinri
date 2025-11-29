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
 * Has One Relationship
 * 
 * Represents a one-to-one relationship where the foreign key is on the related model.
 */
class HasOne extends Relation
{
    /**
     * Get the results of the relationship
     */
    public function getResults(): ?Model
    {
        $localValue = $this->parent->getAttribute($this->localKey);
        
        if ($localValue === null) {
            return null;
        }

        return $this->newQuery()
            ->where($this->foreignKey, $localValue)
            ->first();
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
     * Save a related model
     */
    public function save(Model $model): Model
    {
        $model->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        $model->save();
        
        return $model;
    }
}
