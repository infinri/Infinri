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
 * User Logged In Event
 * 
 * Dispatched when a user successfully logs in.
 */
class UserLoggedIn extends Event
{
    public readonly AuthenticatableInterface $user;
    public readonly bool $remember;
    public readonly ?string $guard;
    public readonly ?string $ip;
    public readonly ?string $userAgent;

    public function __construct(
        AuthenticatableInterface $user,
        bool $remember = false,
        ?string $guard = null,
        ?string $ip = null,
        ?string $userAgent = null
    ) {
        $this->user = $user;
        $this->remember = $remember;
        $this->guard = $guard;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }
}
