<?php declare(strict_types=1);

namespace App\Modules\Contact\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Services\NotificationService;
use League\Plates\Engine;

class ContactController extends Controller
{
    private LoggerInterface $logger;
    private NotificationService $notificationService;

    public function __construct(
        Engine $view,
        ContainerInterface $container,
        LoggerInterface $logger,
        NotificationService $notificationService
    ) {
        parent::__construct($view, $container);
        $this->logger = $logger;
        $this->notificationService = $notificationService;
    }

    /**
     * Show the contact form
     */
    public function showContactForm(Request $request, Response $response): Response
    {
        $data = [
            'title' => 'Contact Us',
            'description' => 'Get in touch with our team.'
        ];
        
        return $this->render($response, 'contact/contact.php', $data);
    }

    /**
     * Handle contact form submission
     */
    public function handleContactForm(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Basic validation
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        }
        
        if (!empty($errors)) {
            // Return to form with errors
            return $this->render($response, 'contact/contact.php', [
                'title' => 'Contact Us',
                'errors' => $errors,
                'formData' => $data
            ]);
        }
        
        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');
        
        // Log the contact form submission
        $this->logger->info('New contact form submission', [
            'name' => $data['name'],
            'email' => $data['email'],
            'ip' => $ipAddress
        ]);
        
        // Prepare email data
        $emailData = [
            'subject' => 'New Contact Form Submission',
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => nl2br(htmlspecialchars($data['message'])),
            'ip' => $ipAddress,
            'timestamp' => $timestamp,
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'referrer' => $request->getHeaderLine('Referer')
        ];
        
        // Send email notification to admin
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
        $adminName = $_ENV['ADMIN_NAME'] ?? 'Site Admin';
        
        $emailSent = $this->notificationService->sendEmail(
            to: $adminEmail,
            subject: "[Contact Form] New message from {$data['name']}",
            template: 'emails/contact-form',
            data: $emailData,
            cc: $_ENV['MAIL_CC'] ?? null,
            bcc: $_ENV['MAIL_BCC'] ?? null
        );
        
        // Send confirmation email to user
        if ($emailSent) {
            $userEmailSent = $this->notificationService->sendEmail(
                to: $data['email'],
                subject: "Thank you for contacting us, {$data['name']}!",
                template: 'emails/contact-confirmation',
                data: [
                    'subject' => 'We\'ve received your message',
                    'name' => $data['name'],
                    'message' => 'Thank you for reaching out to us. We have received your message and will get back to you as soon as possible.'
                ]
            );
            
            if (!$userEmailSent) {
                $this->logger->warning('Failed to send confirmation email to user', [
                    'email' => $data['email']
                ]);
            }
        } else {
            $this->logger->error('Failed to send contact form notification email', [
                'email' => $data['email'],
                'admin_email' => $adminEmail
            ]);
            
            // Log a system notification for admin to check email delivery
            $this->notificationService->sendSystemNotification(
                'Failed to send contact form notification email',
                'error',
                [
                    'email' => $data['email'],
                    'admin_email' => $adminEmail
                ],
                $adminEmail,
                'Contact Form Delivery Failure'
            );
        }
        
        // Redirect to thank you page
        return $this->render($response, 'contact/thank-you.php', [
            'title' => 'Thank You',
            'message' => 'Your message has been sent. We\'ll get back to you soon!'
        ]);
    }
}
