<?php

declare(strict_types=1);

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
