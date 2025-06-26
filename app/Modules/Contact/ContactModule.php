<?php declare(strict_types=1);

namespace App\Modules\Contact;

use App\Modules\Concerns\RegistersViewPaths;
use App\Modules\Concerns\RegistersControllers;
use App\Modules\Contact\Controllers\ContactController;
use App\Modules\Core\Services\NotificationService;
use App\Modules\Module;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Contact module for handling contact form functionality
 * 
 * This module provides contact form handling and notification services.
 * It integrates with the notification system to send contact form submissions.
 */
class ContactModule extends Module
{
    use RegistersViewPaths, RegistersControllers;
    
    /**
     * Register contact module services and configurations
     * 
     * Registers view paths, notification service, and contact controller.
     */
    public function register(): void
    {
        $this->registerViewPaths();
        $this->registerNotificationService();
        $this->registerControllers();
    }

    /**
     * Boot the contact module
     * 
     * This method is called after all modules have been registered.
     */
    public function boot(): void
    {
        // Contact module bootstrapping logic can be added here
    }

    /**
     * Register the notification service if not already registered
     */
    private function registerNotificationService(): void
    {
        if (!$this->container->has(NotificationService::class)) {
            $this->container->set(NotificationService::class, function(ContainerInterface $c) {
                return new NotificationService(
                    $c->get(LoggerInterface::class),
                    null, // API key (null for now as it's a stub)
                    $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
                    $_ENV['MAIL_FROM_NAME'] ?? 'Infinri Notifications',
                    true // Enabled by default
                );
            });
        }
    }

    /**
     * Register contact module controllers
     */
    private function registerControllers(): void
    {
        $this->registerController(ContactController::class, [
            Engine::class,
            'container' => ContainerInterface::class,
            LoggerInterface::class,
            NotificationService::class
        ]);
    }
}
