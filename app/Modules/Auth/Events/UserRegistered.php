<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Events;

use App\Core\Events\Event;
use App\Modules\Auth\Contracts\AuthenticatableInterface;

/**
 * User Registered Event
 * 
 * Dispatched when a new user registers.
 */
class UserRegistered extends Event
{
    public readonly AuthenticatableInterface $user;

    public function __construct(AuthenticatableInterface $user)
    {
        $this->user = $user;
    }
}
