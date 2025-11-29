<?php

declare(strict_types=1);

namespace App\Core\Queue;

use RuntimeException;

/**
 * Queue Exception
 * 
 * Thrown when a queue operation fails.
 */
class QueueException extends RuntimeException
{
}
