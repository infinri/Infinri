<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Authorization;

use App\Core\Error\HttpException;
use App\Core\Http\HttpStatus;
use Throwable;

/**
 * Authorization Exception
 *
 * Thrown when a user is not authorized to perform an action.
 * Extends HttpException for proper HTTP 403 handling.
 */
class AuthorizationException extends HttpException
{
    public function __construct(
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            HttpStatus::FORBIDDEN,
            $message ?? 'This action is unauthorized.',
            $previous
        );
    }
}
