<?php declare(strict_types=1);

namespace App\Modules;

/**
 * Represents the possible states of a module
 */
enum ModuleState: string
{
    case UNINSTALLED = 'uninstalled';
    case INSTALLED = 'installed';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case ERROR = 'error';

    /**
     * Check if the module is active (enabled and not in error state)
     */
    public function isActive(): bool
    {
        return $this === self::ENABLED;
    }

    /**
     * Check if the module can be enabled from the current state
     */
    public function canEnable(): bool
    {
        return match($this) {
            self::INSTALLED, self::DISABLED => true,
            default => false,
        };
    }

    /**
     * Check if the module can be disabled from the current state
     */
    public function canDisable(): bool
    {
        return match($this) {
            self::ENABLED => true,
            default => false,
        };
    }

    /**
     * Check if the module can be installed from the current state
     */
    public function canInstall(): bool
    {
        return $this === self::UNINSTALLED;
    }

    /**
     * Check if the module can be uninstalled from the current state
     */
    public function canUninstall(): bool
    {
        return match($this) {
            self::INSTALLED, self::DISABLED => true,
            default => false,
        };
    }
}
