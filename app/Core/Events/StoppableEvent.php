<?php

declare(strict_types=1);

namespace App\Core\Events;

/**
 * Stoppable Event Interface
 * 
 * Events that can have their propagation stopped.
 */
interface StoppableEvent
{
    /**
     * Check if propagation was stopped
     */
    public function isPropagationStopped(): bool;
}
