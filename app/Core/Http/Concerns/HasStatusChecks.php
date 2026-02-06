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
namespace App\Core\Http\Concerns;

use App\Core\Http\HttpStatus;

/**
 * Provides HTTP status checking methods for Response
 *
 * Delegates status logic to the centralized HttpStatus class.
 */
trait HasStatusChecks
{
    /**
     * Check if status is informational (1xx)
     */
    public function isInformational(): bool
    {
        return HttpStatus::isInformational($this->statusCode);
    }

    /**
     * Check if status is successful (2xx)
     */
    public function isSuccessful(): bool
    {
        return HttpStatus::isSuccessful($this->statusCode);
    }

    /**
     * Check if status is redirect (3xx)
     */
    public function isRedirect(): bool
    {
        return HttpStatus::isRedirect($this->statusCode);
    }

    /**
     * Check if status is client error (4xx)
     */
    public function isClientError(): bool
    {
        return HttpStatus::isClientError($this->statusCode);
    }

    /**
     * Check if status is server error (5xx)
     */
    public function isServerError(): bool
    {
        return HttpStatus::isServerError($this->statusCode);
    }

    /**
     * Check if response is OK (200)
     */
    public function isOk(): bool
    {
        return $this->statusCode === HttpStatus::OK;
    }

    /**
     * Check if response is Not Found (404)
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === HttpStatus::NOT_FOUND;
    }

    /**
     * Check if response is empty
     */
    public function isEmpty(): bool
    {
        return HttpStatus::isEmpty($this->statusCode);
    }
}
