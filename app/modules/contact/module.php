<?php

declare(strict_types=1);

/**
 * Contact Module
 * 
 * Handles contact form and messaging functionality.
 */
return [
    'name' => 'contact',
    'version' => '1.1.0',
    'description' => 'Contact form with email integration',
    'dependencies' => [],
    'providers' => [
        \App\Modules\Contact\Providers\ContactServiceProvider::class,
    ],
    'commands' => [],
    'events' => 'events.php',
    'config' => 'config.php',
    'routes' => null,
    'enabled' => true,
];
