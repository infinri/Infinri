<?php

declare(strict_types=1);

namespace App\Core\Contracts\Module;

/**
 * Installable Interface
 * 
 * For modules that need installation/upgrade/uninstall hooks.
 */
interface InstallableInterface
{
    /**
     * Called when module is first installed
     */
    public function install(): void;

    /**
     * Called when module is upgraded
     * 
     * @param string $fromVersion Previous version
     * @param string $toVersion New version
     */
    public function upgrade(string $fromVersion, string $toVersion): void;

    /**
     * Called when module is uninstalled
     */
    public function uninstall(): void;
}
