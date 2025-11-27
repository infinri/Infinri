<?php

declare(strict_types=1);

/**
 * Contact Module Configuration
 */
return [
    // Rate limiting for contact form
    'rate_limit' => [
        'max_attempts' => 5,
        'decay_minutes' => 60,
    ],
    
    // Form settings
    'form' => [
        'require_name' => true,
        'require_phone' => false,
        'max_message_length' => 5000,
    ],
    
    // Notification settings
    'notifications' => [
        'admin_email' => true,
        'slack_webhook' => false,
    ],
];
