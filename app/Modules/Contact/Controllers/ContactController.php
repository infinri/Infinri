<?php declare(strict_types=1);

namespace App\Modules\Contact\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Modules\Core\Controllers\Controller;
use League\Plates\Engine;

class ContactController extends Controller
{
    private LoggerInterface $logger;

    public function __construct(Engine $view, LoggerInterface $logger)
    {
        parent::__construct($view);
        $this->logger = $logger;
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
        
        // Log the contact form submission
        $this->logger->info('New contact form submission', [
            'name' => $data['name'],
            'email' => $data['email'],
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // TODO: Send email notification
        
        // Redirect to thank you page
        return $this->render($response, 'contact/thank-you.php', [
            'title' => 'Thank You',
            'message' => 'Your message has been sent. We\'ll get back to you soon!'
        ]);
    }
}
