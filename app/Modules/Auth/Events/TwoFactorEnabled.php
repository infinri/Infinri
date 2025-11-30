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
 * Two-Factor Enabled Event
 * 
 * Dispatched when a user enables 2FA.
 */
class TwoFactorEnabled extends Event
{
    public readonly AuthenticatableInterface $user;

    public function __construct(AuthenticatableInterface $user)
    {
        $this->user = $user;
    }
}
