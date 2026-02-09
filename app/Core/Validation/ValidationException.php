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
namespace App\Core\Validation;

use App\Core\Error\HttpException;

/**
 * Validation Exception
 *
 * Thrown when validation fails. Contains the validator instance
 * with all error details. Extends HttpException for proper 422 status.
 */
class ValidationException extends HttpException
{
    /**
     * The validator instance
     */
    protected Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;

        $message = 'The given data was invalid.';
        $errors = $validator->errors();

        if ($errors !== []) {
            $message = reset($errors);
        }

        parent::__construct(422, $message);
    }

    /**
     * Get the validator instance
     */
    public function validator(): Validator
    {
        return $this->validator;
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->validator->errors();
    }

    /**
     * Get response data for API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors(),
        ];
    }
}
