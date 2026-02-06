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
namespace App\Core\Database\Concerns;

use App\Core\Database\Model;
use App\Core\Database\Relations\BelongsTo;
use App\Core\Database\Relations\BelongsToMany;
use App\Core\Database\Relations\HasMany;
use App\Core\Database\Relations\HasOne;
use App\Core\Support\Str;

/**
 * Has Relationships Trait
 *
 * Provides relationship definition methods for models.
 */
trait HasRelationships
{
    /**
     * Loaded relationships
     */
    protected array $relations = [];

    /**
     * Define a one-to-one relationship
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related();

        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->getKeyName();

        return new HasOne($this, $instance, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related();

        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->getKeyName();

        return new HasMany($this, $instance, $foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();

        // Default foreign key is related_model_id
        if ($foreignKey === null) {
            $foreignKey = Str::snake(Str::classBasename($related)) . '_id';
        }

        $ownerKey ??= $instance->getKeyName();

        return new BelongsTo($this, $instance, $foreignKey, $ownerKey);
    }

    /**
     * Define a many-to-many relationship
     */
    public function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignKey = null,
        ?string $relatedKey = null,
        ?string $parentKey = null,
        ?string $relatedLocalKey = null
    ): BelongsToMany {
        $instance = new $related();

        // Default pivot table name is both model names in alphabetical order
        if ($pivotTable === null) {
            $pivotTable = $this->joiningTable($related);
        }

        $foreignKey ??= $this->getForeignKey();
        $relatedKey ??= $instance->getForeignKey();
        $parentKey ??= $this->getKeyName();
        $relatedLocalKey ??= $instance->getKeyName();

        return new BelongsToMany(
            $this,
            $instance,
            $pivotTable,
            $foreignKey,
            $relatedKey,
            $parentKey,
            $relatedLocalKey
        );
    }

    /**
     * Get the foreign key for this model
     */
    public function getForeignKey(): string
    {
        return Str::snake(Str::classBasename($this)) . '_id';
    }

    /**
     * Get the primary key name
     */
    public function getKeyName(): string
    {
        return $this->primaryKey ?? 'id';
    }

    /**
     * Get the joining table name for a many-to-many relationship
     */
    protected function joiningTable(string $related): string
    {
        $models = [
            Str::snake(Str::classBasename($this)),
            Str::snake(Str::classBasename($related)),
        ];

        sort($models);

        return implode('_', $models);
    }

    /**
     * Get a relationship value
     */
    public function getRelation(string $name): mixed
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * Set a relationship value
     */
    public function setRelation(string $name, mixed $value): static
    {
        $this->relations[$name] = $value;

        return $this;
    }

    /**
     * Check if a relationship is loaded
     */
    public function relationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * Get all loaded relationships
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Load a relationship
     */
    public function load(string|array $relations): static
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($relations as $relation) {
            if (! $this->relationLoaded($relation)) {
                $this->relations[$relation] = $this->$relation()->getResults();
            }
        }

        return $this;
    }
}
