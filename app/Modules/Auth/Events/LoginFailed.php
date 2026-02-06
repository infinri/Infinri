<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Events;

use App\Core\Events\Event;

/**
 * Login Failed Event
 * 
 * Dispatched when a login attempt fails.
 */
class LoginFailed extends Event
{
    public readonly string $email;
    public readonly ?string $ip;
    public readonly ?string $userAgent;
    public readonly string $reason;

    public function __construct(
        string $email,
        string $reason = 'invalid_credentials',
        ?string $ip = null,
        ?string $userAgent = null
    ) {
        $this->email = $email;
        $this->reason = $reason;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }
}
