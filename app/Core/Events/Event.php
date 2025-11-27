<?php

declare(strict_types=1);

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
