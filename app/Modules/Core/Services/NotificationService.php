<?php declare(strict_types=1);

namespace App\Modules\Core\Services;

use Psr\Log\LoggerInterface;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\SendSmtpEmailReplyTo;
use Exception;
use GuzzleHttp\Client;

class NotificationService
{
    private ?TransactionalEmailsApi $brevoClient;
    private LoggerInterface $logger;
    private string $fromEmail;
    private string $fromName;
    private bool $enabled;

    public function __construct(
        LoggerInterface $logger,
        string $apiKey = null,
        string $fromEmail = 'noreply@example.com',
        string $fromName = 'Notification System',
        bool $enabled = true
    ) {
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->enabled = $enabled;

        if ($apiKey && $enabled) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $this->brevoClient = new TransactionalEmailsApi(
                new Client(),
                $config
            );
        } else {
            $this->brevoClient = null;
        }
    }

    /**
     * Send an email using Brevo
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $template Path to the template file
     * @param array $data Template variables
     * @param string|null $cc CC email address (optional)
     * @param string|null $bcc BCC email address (optional)
     * @param array $attachments Array of file paths to attach
     * @return bool True if email was sent successfully
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        ?string $cc = null,
        ?string $bcc = null,
        array $attachments = []
    ): bool {
        if (!$this->enabled) {
            $this->logger->info('Email sending is disabled', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            return false;
        }

        if (!$this->brevoClient) {
            $this->logger->error('Brevo client not initialized. Check your API key configuration.');
            return false;
        }

        try {
            $this->logger->info('Preparing to send email', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);

            // Prepare recipients
            $toRecipients = [new SendSmtpEmailTo(['email' => $to, 'name' => $data['name'] ?? ''])];
            $ccRecipients = $cc ? [new SendSmtpEmailTo(['email' => $cc])] : [];
            $bccRecipients = $bcc ? [new SendSmtpEmailTo(['email' => $bcc])] : [];

            // Prepare email content
            $email = new SendSmtpEmail([
                'to' => $toRecipients,
                'cc' => $ccRecipients,
                'bcc' => $bccRecipients,
                'subject' => $subject,
                'htmlContent' => $this->renderTemplate($template, $data, true),
                'textContent' => $this->renderTemplate($template, $data, false),
                'sender' => ['name' => $this->fromName, 'email' => $this->fromEmail],
                'replyTo' => new SendSmtpEmailReplyTo([
                    'email' => $this->fromEmail,
                    'name' => $this->fromName
                ]),
                'headers' => [
                    'X-Mailer' => 'Infinri Mailer',
                    'X-Priority' => '1',
                ]
            ]);

            // Add attachments if any
            if (!empty($attachments)) {
                $email->setAttachment($this->prepareAttachments($attachments));
            }

            // Send the email
            $response = $this->brevoClient->sendTransacEmail($email);

            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'message_id' => $response->getMessageId()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send email: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Render email template with provided data
     */
    private function renderTemplate(string $template, array $data, bool $html = true): string
    {
        // This is a simplified implementation. In a real app, you might use a templating engine
        $content = '';
        
        try {
            // For now, we'll just return a simple message
            // In a real implementation, you would use a templating engine here
            if ($html) {
                $content = "<html><body>";
                $content .= "<h1>" . ($data['subject'] ?? 'Notification') . "</h1>";
                $content .= "<p>" . ($data['message'] ?? '') . "</p>";
                $content .= "</body></html>";
            } else {
                $content = $data['message'] ?? '';
            }
            
            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Failed to render email template', [
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return $html ? '<p>Error rendering email template</p>' : 'Error rendering email template';
        }
    }

    /**
     * Prepare file attachments for the email
     */
    private function prepareAttachments(array $filePaths): array
    {
        $attachments = [];
        
        foreach ($filePaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                $attachments[] = [
                    'name' => basename($path),
                    'content' => base64_encode(file_get_contents($path))
                ];
            } else {
                $this->logger->warning('Attachment file not found or not readable', ['path' => $path]);
            }
        }
        
        return $attachments;
    }

    /**
     * Send a system notification (log message with optional email)
     */
    public function sendSystemNotification(
        string $message,
        string $level = 'info',
        array $context = [],
        ?string $emailRecipient = null,
        string $emailSubject = 'System Notification'
    ): void {
        // Log the message
        $method = 'log' . ucfirst(strtolower($level));
        if (method_exists($this->logger, $method)) {
            $this->logger->$method($message, $context);
        } else {
            $this->logger->info($message, $context);
        }

        // Optionally send email for critical errors
        if ($emailRecipient && in_array(strtolower($level), ['error', 'critical', 'emergency', 'alert'])) {
            $this->sendEmail(
                $emailRecipient,
                "[$level] $emailSubject",
                'system/notification',
                [
                    'subject' => $emailSubject,
                    'message' => $message,
                    'level' => $level,
                    'context' => $context
                ]
            );
        }
    }
}
