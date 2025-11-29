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
namespace App\Core\Database;

use App\Core\Support\Str;

/**
 * Model Factory
 * 
 * Creates model instances for testing and seeding.
 */
abstract class Factory
{
    /**
     * The model class
     */
    protected string $model;

    /**
     * Number of models to create
     */
    protected int $count = 1;

    /**
     * State modifications
     */
    protected array $states = [];

    /**
     * Define the model's default state
     */
    abstract public function definition(): array;

    /**
     * Set the number of models to create
     */
    public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = $count;
        return $clone;
    }

    /**
     * Apply a state transformation
     */
    public function state(array|callable $state): static
    {
        $clone = clone $this;
        $clone->states[] = $state;
        return $clone;
    }

    /**
     * Create a model instance without persisting
     */
    public function make(array $attributes = []): Model|array
    {
        if ($this->count === 1) {
            return $this->makeOne($attributes);
        }

        $models = [];
        for ($i = 0; $i < $this->count; $i++) {
            $models[] = $this->makeOne($attributes);
        }
        return $models;
    }

    /**
     * Create and persist a model instance
     */
    public function create(array $attributes = []): Model|array
    {
        if ($this->count === 1) {
            return $this->createOne($attributes);
        }

        $models = [];
        for ($i = 0; $i < $this->count; $i++) {
            $models[] = $this->createOne($attributes);
        }
        return $models;
    }

    /**
     * Make a single model
     */
    protected function makeOne(array $attributes = []): Model
    {
        $definition = $this->definition();

        // Apply states
        foreach ($this->states as $state) {
            if (is_callable($state)) {
                $state = $state($definition);
            }
            $definition = array_merge($definition, $state);
        }

        // Apply overrides
        $definition = array_merge($definition, $attributes);

        return new $this->model($definition);
    }

    /**
     * Create and persist a single model
     */
    protected function createOne(array $attributes = []): Model
    {
        $model = $this->makeOne($attributes);
        $model->save();
        return $model;
    }

    /**
     * Get a new factory instance for the model
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Generate a random string
     */
    protected function randomString(int $length = 10): string
    {
        return strtolower(Str::random($length));
    }

    /**
     * Generate a random email
     */
    protected function randomEmail(): string
    {
        return strtolower(Str::random(8)) . '@example.com';
    }

    /**
     * Generate a random number
     */
    protected function randomNumber(int $min = 0, int $max = 1000): int
    {
        return random_int($min, $max);
    }

    /**
     * Generate a random boolean
     */
    protected function randomBool(): bool
    {
        return (bool) random_int(0, 1);
    }

    /**
     * Generate a random date
     */
    protected function randomDate(string $start = '-1 year', string $end = 'now'): string
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        $randomTs = random_int($startTs, $endTs);
        return date('Y-m-d H:i:s', $randomTs);
    }
}
