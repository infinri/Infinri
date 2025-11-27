<?php

declare(strict_types=1);

/**
 * Contact Module Hooks
 * 
 * Lifecycle hooks for module setup and upgrades.
 */
return [
    /**
     * Called when module is first installed
     */
    'onInstall' => function () {
        // Example: Create required database tables
        // Example: Set default configuration
    },

    /**
     * Called when module version is upgraded
     * 
     * @param string $fromVersion Previous version
     */
    'onUpgrade' => function (string $fromVersion) {
        // Example: Run data migrations
        // if (version_compare($fromVersion, '1.1.0', '<')) {
        //     // Migrate data for 1.1.0
        // }
    },

    /**
     * Called before setup runs
     */
    'beforeSetup' => function () {
        // Example: Prepare module for setup
    },

    /**
     * Called after setup completes
     */
    'afterSetup' => function () {
        // Example: Clear module-specific caches
        // Example: Rebuild indexes
    },
];
