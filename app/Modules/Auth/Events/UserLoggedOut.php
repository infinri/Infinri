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
 * User Logged Out Event
 * 
 * Dispatched when a user logs out.
 */
class UserLoggedOut extends Event
{
    public readonly ?AuthenticatableInterface $user;
    public readonly ?string $guard;

    public function __construct(?AuthenticatableInterface $user = null, ?string $guard = null)
    {
        $this->user = $user;
        $this->guard = $guard;
    }
}
