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
