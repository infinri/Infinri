<?php

declare(strict_types=1);

namespace App\Core\Database;

/**
 * Model Not Found Exception
 * 
 * Thrown when a model cannot be found.
 */
class ModelNotFoundException extends DatabaseException
{
    protected string $model;
    protected int|string $id;

    public function __construct(string $model, int|string $id)
    {
        $this->model = $model;
        $this->id = $id;

        $message = "Model [{$model}] with ID [{$id}] not found.";
        
        parent::__construct($message, 404);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getId(): int|string
    {
        return $this->id;
    }
}
