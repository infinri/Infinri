<?php
declare(strict_types=1);
/**
 * Service Options Configuration
 * 
 * IMPORTANT: This is where you configure the contact form service dropdown.
 * Easy to customize for different projects/clients.
 * 
 * Format:
 * 'value' => 'Display Label'
 */

return [
    // General/Other option (always first)
    'general' => 'General Inquiry',
    
    // Monthly Plans
    'be-my-developer' => 'Be My Developer ($10/mo)',
    'be-my-it-guy' => 'Be My IT Guy ($15-$20/mo)',
    
    // Website Packages
    'basic-template' => 'Basic Template ($10)',
    'standard-website' => 'Standard Website ($25)',
    'premium-website' => 'Premium Website ($50)',
    
    // One-Time Services
    'website-audit' => 'Website Audit ($75)',
    'website-fixes' => 'Website Fixes ($20)',
    'website-improvements' => 'Website Improvements ($25)',
    
    // Other option (always last)
    'other' => 'Other / Not Sure Yet',
];
