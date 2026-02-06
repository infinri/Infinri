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
namespace App\Core\Support;

use RuntimeException;

/**
 * Circuit Breaker Open Exception
 *
 * Thrown when attempting to call a service whose circuit breaker is open.
 */
class CircuitBreakerOpenException extends RuntimeException
{
}
