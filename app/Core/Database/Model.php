<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Application;
use App\Core\Contracts\Database\ConnectionInterface;
use App\Core\Database\Concerns\HasAttributes;
use App\Core\Database\Concerns\HasRelationships;
use App\Core\Support\Str;
use JsonSerializable;

/**
 * Active Record Model
 * 
 * Base class for all database models implementing the Active Record pattern.
 */
abstract class Model implements JsonSerializable
{
    use HasAttributes;
    use HasRelationships;

    /**
     * The table associated with the model
     */
    protected string $table = '';

    /**
     * The primary key for the model
     */
    protected string $primaryKey = 'id';

    /**
     * The primary key type
     */
    protected string $keyType = 'int';

    /**
     * Indicates if the model's ID is auto-incrementing
     */
    protected bool $incrementing = true;

    /**
     * Indicates if the model exists in the database
     */
    protected bool $exists = false;

    /**
     * The connection name for the model
     */
    protected ?string $connection = null;

    /**
     * Indicates if timestamps should be managed
     */
    protected bool $timestamps = true;

    /**
     * The name of the "created at" column
     */
    protected string $createdAt = 'created_at';

    /**
     * The name of the "updated at" column
     */
    protected string $updatedAt = 'updated_at';

    /**
     * Create a new model instance
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the model's primary key
     */
    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Get the table associated with the model
     */
    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }

        // Convert class name to snake_case and pluralize
        return Str::snake(Str::classBasename($this)) . 's';
    }

    /**
     * Get the database connection
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->resolveConnection()->connection($this->connection);
    }

    /**
     * Get the database manager
     */
    protected function resolveConnection(): DatabaseManager
    {
        return Application::getInstance()->make(DatabaseManager::class);
    }

    /**
     * Get a new query builder for the model
     */
    public static function query(): ModelQueryBuilder
    {
        $instance = new static();
        return new ModelQueryBuilder($instance);
    }

    /**
     * Find a model by its primary key
     */
    public static function find(int|string $id): ?static
    {
        return static::query()->find($id);
    }

    /**
     * Find a model by its primary key or throw an exception
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new ModelNotFoundException(static::class, $id);
        }

        return $model;
    }

    /**
     * Get all models
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Create a new model and save it
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Save the model to the database
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Perform an insert operation
     */
    protected function performInsert(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->setAttribute($this->createdAt, $now);
            $this->setAttribute($this->updatedAt, $now);
        }

        $attributes = $this->attributes;
        
        // Remove primary key if auto-incrementing
        if ($this->incrementing) {
            unset($attributes[$this->primaryKey]);
        }

        $id = $this->getConnection()
            ->table($this->getTable())
            ->insert($attributes);

        if ($this->incrementing) {
            $this->setAttribute($this->primaryKey, $id);
        }

        $this->exists = true;
        $this->original = $this->attributes;

        return true;
    }

    /**
     * Perform an update operation
     */
    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        if ($this->timestamps) {
            $dirty[$this->updatedAt] = date('Y-m-d H:i:s');
            $this->setAttribute($this->updatedAt, $dirty[$this->updatedAt]);
        }

        $this->getConnection()
            ->table($this->getTable())
            ->where($this->primaryKey, $this->getKey())
            ->update($dirty);

        $this->original = $this->attributes;

        return true;
    }

    /**
     * Delete the model from the database
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->getConnection()
            ->table($this->getTable())
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        return true;
    }

    /**
     * Refresh the model from the database
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            return $this;
        }

        $fresh = static::find($this->getKey());

        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
        }

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create a new model instance from database results
     */
    public function newFromBuilder(array $attributes): static
    {
        $model = new static();
        $model->exists = true;
        $model->attributes = $attributes;
        $model->original = $attributes;

        return $model;
    }

    /**
     * Dynamically retrieve attributes
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset an attribute
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Handle dynamic static method calls
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static())->$method(...$parameters);
    }

    /**
     * Handle dynamic method calls
     */
    public function __call(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }
}
