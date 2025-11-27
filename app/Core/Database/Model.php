<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Application;
use App\Core\Contracts\Database\ConnectionInterface;
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
     * The model's attributes
     */
    protected array $attributes = [];

    /**
     * The model's original attributes
     */
    protected array $original = [];

    /**
     * Indicates if the model exists in the database
     */
    protected bool $exists = false;

    /**
     * The connection name for the model
     */
    protected ?string $connection = null;

    /**
     * The attributes that should be cast
     */
    protected array $casts = [];

    /**
     * The attributes that are mass assignable
     */
    protected array $fillable = [];

    /**
     * The attributes that aren't mass assignable
     */
    protected array $guarded = ['*'];

    /**
     * The attributes that should be hidden for serialization
     */
    protected array $hidden = [];

    /**
     * The attributes that should be visible in serialization
     */
    protected array $visible = [];

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
     * Fill the model with an array of attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Determine if a given attribute is fillable
     */
    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return !in_array($key, $this->guarded);
    }

    /**
     * Set an attribute on the model
     */
    public function setAttribute(string $key, mixed $value): static
    {
        // Check for mutator
        $mutator = 'set' . Str::studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the model
     */
    public function getAttribute(string $key): mixed
    {
        // Check for accessor
        $accessor = 'get' . Str::studly($key) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor($this->attributes[$key] ?? null);
        }

        $value = $this->attributes[$key] ?? null;

        // Apply casts
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * Cast an attribute to a native PHP type
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        $castType = $this->casts[$key];

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => is_string($value) ? json_decode($value, true) : (array) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value ? new \DateTime($value) : null,
            default => $value,
        };
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
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
     * Get the dirty (changed) attributes
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Determine if the model has been modified
     */
    public function isDirty(): bool
    {
        return !empty($this->getDirty());
    }

    /**
     * Sync the original attributes with the current
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
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
     * Convert the model to an array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        // Apply hidden
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        // Apply visible
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }

        return $attributes;
    }

    /**
     * Convert the model to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
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
