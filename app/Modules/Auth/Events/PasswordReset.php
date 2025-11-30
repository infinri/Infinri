<?php

declare(strict_types=1);

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
 * Password Reset Event
 * 
 * Dispatched when a user resets their password.
 */
class PasswordReset extends Event
{
    public readonly AuthenticatableInterface $user;

    public function __construct(AuthenticatableInterface $user)
    {
        $this->user = $user;
    }
}
