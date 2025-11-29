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
