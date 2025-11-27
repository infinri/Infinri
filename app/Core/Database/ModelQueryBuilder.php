<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Model Query Builder
 * 
 * Extends the base query builder with model-specific functionality.
 */
class ModelQueryBuilder
{
    protected Model $model;
    protected QueryBuilder $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $model->getConnection()->table($model->getTable());
    }

    /**
     * Find a model by its primary key
     */
    public function find(int|string $id): ?Model
    {
        $result = $this->query
            ->where($this->model->primaryKey ?? 'id', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        return $this->model->newFromBuilder($result);
    }

    /**
     * Get all models matching the query
     */
    public function get(): array
    {
        $results = $this->query->get();

        return array_map(
            fn(array $attributes) => $this->model->newFromBuilder($attributes),
            $results
        );
    }

    /**
     * Get the first model matching the query
     */
    public function first(): ?Model
    {
        $result = $this->query->first();

        if ($result === null) {
            return null;
        }

        return $this->model->newFromBuilder($result);
    }

    /**
     * Get the count of models
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Check if any models exist
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * Add a WHERE clause
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add an OR WHERE clause
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        $this->query->orWhere($column, $operator, $value);
        return $this;
    }

    /**
     * Add a WHERE IN clause
     */
    public function whereIn(string $column, array $values): static
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Add a WHERE NULL clause
     */
    public function whereNull(string $column): static
    {
        $this->query->whereNull($column);
        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause
     */
    public function whereNotNull(string $column): static
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    /**
     * Add an ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Order by latest (created_at desc)
     */
    public function latest(?string $column = null): static
    {
        $column = $column ?? ($this->model->createdAt ?? 'created_at');
        return $this->orderBy($column, 'desc');
    }

    /**
     * Order by oldest (created_at asc)
     */
    public function oldest(?string $column = null): static
    {
        $column = $column ?? ($this->model->createdAt ?? 'created_at');
        return $this->orderBy($column, 'asc');
    }

    /**
     * Add a LIMIT clause
     */
    public function limit(int $limit): static
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Alias for limit
     */
    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    /**
     * Add an OFFSET clause
     */
    public function offset(int $offset): static
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Alias for offset
     */
    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    /**
     * Select specific columns
     */
    public function select(string|array $columns = ['*']): static
    {
        $this->query->select($columns);
        return $this;
    }

    /**
     * Delete the matching records
     */
    public function delete(): int
    {
        return $this->query->delete();
    }

    /**
     * Update the matching records
     */
    public function update(array $values): int
    {
        return $this->query->update($values);
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Get the SQL representation of the query
     */
    public function toSql(): string
    {
        return $this->query->toSql();
    }

    /**
     * Forward calls to the query builder
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->$method(...$parameters);

        // If the query builder returns itself, return this instead
        if ($result instanceof QueryBuilder) {
            return $this;
        }

        return $result;
    }
}
