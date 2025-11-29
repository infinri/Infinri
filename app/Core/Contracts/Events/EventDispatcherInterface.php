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
namespace App\Core\Contracts\Events;

/**
 * Event Dispatcher Contract
 * 
 * Defines the event dispatching mechanism.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners
     */
    public function dispatch(object $event): object;

    /**
     * Register an event listener
     */
    public function listen(string $event, callable|string $listener, int $priority = 0): void;

    /**
     * Register a subscriber (class with multiple listeners)
     */
    public function subscribe(string|object $subscriber): void;

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool;

    /**
     * Get all listeners for an event
     */
    public function getListeners(string $event): array;

    /**
     * Remove a listener from an event
     */
    public function forget(string $event, ?callable $listener = null): void;
}
