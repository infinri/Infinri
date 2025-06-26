<?php declare(strict_types=1);

namespace App\Modules\Core\Services;

use Psr\Log\LoggerInterface;

class NotificationService
{
    private $mailer;
    private $logger;
    private $fromEmail;
    private $fromName;

    public function __construct(
        $mailer, 
        LoggerInterface $logger,
        string $fromEmail = 'noreply@example.com',
        string $fromName = 'Notification System'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Send an email notification
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        ?string $cc = null,
        ?string $bcc = null
    ): bool {
        try {
            // In a real implementation, this would use a mailer service
            $this->logger->info('Sending email', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);

            // Simulate sending email
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Send a system notification
     */
    public function sendSystemNotification(
        string $message,
        string $level = 'info',
        array $context = []
    ): void {
        $method = 'log' . ucfirst(strtolower($level));
        if (method_exists($this->logger, $method)) {
            $this->logger->$method($message, $context);
        } else {
            $this->logger->info($message, $context);
        }
    }
}
