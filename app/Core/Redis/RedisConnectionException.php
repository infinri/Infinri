<?php

declare(strict_types=1);

namespace App\Core\Redis;

use RuntimeException;

/**
 * Redis Connection Exception
 * 
 * Thrown when a Redis connection cannot be established.
 */
class RedisConnectionException extends RuntimeException
{
}
