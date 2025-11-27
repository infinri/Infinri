<?php

declare(strict_types=1);

/**
 * Contact Module Events
 * 
 * Format:
 * EventClass::class => [
 *     ListenerClass::class,
 *     [ListenerClass::class, 'methodName', priority],
 * ],
 */
return [
    // Example: when contact form is submitted
    // \App\Modules\Contact\Events\ContactSubmitted::class => [
    //     \App\Modules\Contact\Listeners\SendAdminNotification::class,
    //     \App\Modules\Contact\Listeners\AddToMailingList::class,
    // ],
];
