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
namespace App\Core\Events;

/**
 * Base Event Class
 *
 * Base class for all application events.
 */
abstract class Event implements StoppableEvent
{
    /**
     * Whether propagation was stopped
     */
    protected bool $propagationStopped = false;

    /**
     * Stop event propagation
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if propagation was stopped
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
