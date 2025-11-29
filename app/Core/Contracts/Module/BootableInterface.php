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
namespace App\Core\Contracts\Module;

/**
 * Bootable Interface
 * 
 * For modules or providers that need a boot phase after registration.
 */
interface BootableInterface
{
    /**
     * Boot the component
     * 
     * Called after all services are registered.
     */
    public function boot(): void;
}
