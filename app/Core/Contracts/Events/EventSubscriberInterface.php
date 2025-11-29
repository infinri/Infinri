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
 * Event Subscriber Interface
 * 
 * Defines the contract for event subscribers that listen to multiple events.
 * Subscribers are automatically registered with the EventDispatcher.
 * 
 * Example implementation:
 * ```php
 * class UserEventSubscriber implements EventSubscriberInterface
 * {
 *     public static function getSubscribedEvents(): array
 *     {
 *         return [
 *             'user.created' => 'onUserCreated',
 *             'user.deleted' => ['onUserDeleted', 10], // with priority
 *             'user.updated' => [
 *                 ['onUserUpdated', 0],
 *                 ['sendNotification', -10],
 *             ],
 *         ];
 *     }
 * 
 *     public function onUserCreated(UserCreatedEvent $event): void { }
 *     public function onUserDeleted(UserDeletedEvent $event): void { }
 * }
 * ```
 */
interface EventSubscriberInterface
{
    /**
     * Get the events this subscriber listens to
     * 
     * Returns an associative array where:
     * - Key: event name
     * - Value: method name, [method, priority], or array of [method, priority]
     * 
     * Priority: Higher numbers = earlier execution. Default is 0.
     * 
     * @return array<string, string|array>
     */
    public static function getSubscribedEvents(): array;
}
