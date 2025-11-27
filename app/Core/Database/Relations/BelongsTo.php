<?php

declare(strict_types=1);

namespace App\Core\Database\Relations;

use App\Core\Database\Model;

/**
 * Belongs To Relationship
 * 
 * Represents the inverse of a one-to-one or one-to-many relationship.
 * The foreign key is on the parent model.
 */
class BelongsTo extends Relation
{
    protected string $ownerKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $ownerKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
        $this->localKey = $foreignKey;
    }

    /**
     * Get the results of the relationship
     */
    public function getResults(): ?Model
    {
        $foreignValue = $this->parent->getAttribute($this->foreignKey);
        
        if ($foreignValue === null) {
            return null;
        }

        return $this->newQuery()
            ->where($this->ownerKey, $foreignValue)
            ->first();
    }

    /**
     * Associate the parent with a related model
     */
    public function associate(Model $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));
        
        return $this->parent;
    }

    /**
     * Dissociate the parent from the related model
     */
    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);
        
        return $this->parent;
    }

    /**
     * Get the owner key
     */
    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }
}
