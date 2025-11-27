<?php

declare(strict_types=1);

namespace App\Core\Database\Relations;

use App\Core\Database\Model;

/**
 * Belongs To Many Relationship
 * 
 * Represents a many-to-many relationship through a pivot table.
 */
class BelongsToMany extends Relation
{
    protected string $pivotTable;
    protected string $parentKey;
    protected string $relatedKey;
    protected array $pivotColumns = [];

    public function __construct(
        Model $parent,
        Model $related,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey,
        string $parentKey = 'id',
        string $relatedLocalKey = 'id'
    ) {
        $this->parent = $parent;
        $this->related = $related;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->relatedKey = $relatedKey;
        $this->parentKey = $parentKey;
        $this->localKey = $relatedLocalKey;
    }

    /**
     * Get the results of the relationship
     */
    public function getResults(): array
    {
        $parentValue = $this->parent->getAttribute($this->parentKey);
        
        if ($parentValue === null) {
            return [];
        }

        $pivotColumns = empty($this->pivotColumns) 
            ? '' 
            : ', ' . implode(', ', array_map(fn($col) => "pivot.{$col}", $this->pivotColumns));

        $query = $this->related->getConnection()
            ->query()
            ->table($this->related->getTable() . ' as related')
            ->select("related.*{$pivotColumns}")
            ->join(
                $this->pivotTable . ' as pivot',
                "related.{$this->localKey}",
                '=',
                "pivot.{$this->relatedKey}"
            )
            ->where("pivot.{$this->foreignKey}", $parentValue);

        $results = $query->get();

        return array_map(
            fn(array $attributes) => $this->related->newFromBuilder($attributes),
            $results
        );
    }

    /**
     * Attach a model to the pivot table
     */
    public function attach(int|string|array $ids, array $attributes = []): void
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $parentValue = $this->parent->getAttribute($this->parentKey);

        foreach ($ids as $id) {
            $record = array_merge([
                $this->foreignKey => $parentValue,
                $this->relatedKey => $id,
            ], $attributes);

            $this->parent->getConnection()
                ->table($this->pivotTable)
                ->insert($record);
        }
    }

    /**
     * Detach models from the pivot table
     */
    public function detach(int|string|array|null $ids = null): int
    {
        $parentValue = $this->parent->getAttribute($this->parentKey);
        
        $query = $this->parent->getConnection()
            ->table($this->pivotTable)
            ->where($this->foreignKey, $parentValue);

        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            $query->whereIn($this->relatedKey, $ids);
        }

        return $query->delete();
    }

    /**
     * Sync the pivot table with the given IDs
     */
    public function sync(array $ids): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
        ];

        // Get current IDs
        $current = array_map(
            fn($model) => $model->getAttribute($this->localKey),
            $this->getResults()
        );

        // Determine what to attach and detach
        $toAttach = array_diff($ids, $current);
        $toDetach = array_diff($current, $ids);

        // Detach
        if (!empty($toDetach)) {
            $this->detach($toDetach);
            $changes['detached'] = array_values($toDetach);
        }

        // Attach
        if (!empty($toAttach)) {
            $this->attach($toAttach);
            $changes['attached'] = array_values($toAttach);
        }

        return $changes;
    }

    /**
     * Toggle the attachment of the given IDs
     */
    public function toggle(array $ids): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
        ];

        $current = array_map(
            fn($model) => $model->getAttribute($this->localKey),
            $this->getResults()
        );

        foreach ($ids as $id) {
            if (in_array($id, $current)) {
                $this->detach($id);
                $changes['detached'][] = $id;
            } else {
                $this->attach($id);
                $changes['attached'][] = $id;
            }
        }

        return $changes;
    }

    /**
     * Specify which pivot columns to retrieve
     */
    public function withPivot(string|array $columns): static
    {
        $this->pivotColumns = array_merge(
            $this->pivotColumns,
            is_array($columns) ? $columns : func_get_args()
        );

        return $this;
    }

    /**
     * Get the pivot table name
     */
    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }
}
