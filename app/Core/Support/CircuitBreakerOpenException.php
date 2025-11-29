<?php

declare(strict_types=1);

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
