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

use App\Core\Support\Str;
use DateTime;

/**
 * Has Attributes
 *
 * Handles model attribute management: getting, setting, casting, filling.
 */
trait HasAttributes
{
    /**
     * The model's attributes
     */
    protected array $attributes = [];

    /**
     * The model's original attributes
     */
    protected array $original = [];

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
        if (in_array($key, $this->fillable, true)) {
            return true;
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return ! in_array($key, $this->guarded, true);
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
            'datetime' => $value ? new DateTime($value) : null,
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
     * Get attributes that have been changed
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Check if the model has been modified
     */
    public function isDirty(): bool
    {
        return count($this->getDirty()) > 0;
    }

    /**
     * Sync original attributes with current
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $key => $value) {
            // Skip hidden attributes
            if (in_array($key, $this->hidden, true)) {
                continue;
            }

            // If visible is set, only include those
            if (! empty($this->visible) && ! in_array($key, $this->visible, true)) {
                continue;
            }

            $array[$key] = $this->getAttribute($key);
        }

        return $array;
    }

    /**
     * Convert model to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
