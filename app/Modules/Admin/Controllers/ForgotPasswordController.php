<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AdminUser;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

class ForgotPasswordController extends BaseAdminController
{
    /** @var TransportInterface */
    private $mailer;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerInterface::class);
        $this->mailer = $container->get(TransportInterface::class);
    }

    /**
     * Show the forgot password form
     */
    public function showLinkRequestForm(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'admin/auth/passwords/email', [
            'title' => 'Reset Password',
            'email' => $request->getQueryParams()['email'] ?? '',
        ]);
    }

    /**
     * Handle the password reset link request
     */
    public function sendResetLinkEmail(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        
        // Validate email
        $validation = v::email()->validate($email);
        
        if (!$validation) {
            $this->flash->addMessage('error', 'Please provide a valid email address.');
            return $this->redirect('admin.password.request', [], ['email' => $email]);
        }
        
        // Find user by email
        $user = $this->getUserRepository()->findByEmail($email);
        
        // Always return success to prevent email enumeration attacks
        if (!$user || !$user->isActive()) {
            $this->logger->info('Password reset requested for non-existent or inactive account', [
                'email' => $email,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            return $this->sendResetLinkResponse($response);
        }
        
        // Create and save password reset token
        $token = $this->getTokenRepository()->createToken($user->email);
        
        // Send password reset email
        $this->sendPasswordResetEmail($user, $token);
        
        $this->logger->info('Password reset link sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        return $this->sendResetLinkResponse($response);
    }
    
    /**
     * Send the password reset notification
     */
    protected function sendPasswordResetEmail(AdminUser $user, string $token): void
    {
        $resetUrl = $this->router->fullUrlFor(
            $this->request->getUri(),
            'admin.password.reset',
            ['token' => $token]
        );
        
        $appName = $this->container->get('settings')['app']['name'] ?? 'Admin';
        $subject = "Reset Your {$appName} Password";
        
        // Create HTML email content
        $html = new MimePart($this->view->fetch('admin/emails/password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'appName' => $appName,
        ]));
        $html->type = 'text/html';
        
        // Create plain text version
        $text = new MimePart("To reset your password, please visit: {$resetUrl}\n\n");
        $text->type = 'text/plain';
        
        $body = new MimeMessage();
        $body->setParts([$html, $text]);
        
        // Create and send the message
        $message = new Message();
        $message->addTo($user->email, $user->name)
                ->addFrom(
                    $this->container->get('settings')['mail']['from']['email'] ?? 'noreply@example.com',
                    $this->container->get('settings')['mail']['from']['name'] ?? 'Admin'
                )
                ->setSubject($subject)
                ->setBody($body);
        
        $this->mailer->send($message);
    }
    
    /**
     * Get the response for a successful password reset link
     */
    protected function sendResetLinkResponse(Response $response): Response
    {
        $this->flash->addMessage('success', 'We have emailed your password reset link!');
        return $this->redirect('admin.password.request');
    }
    
    /**
     * Get the user repository
     */
    private function getUserRepository()
    {
        return $this->container->get('orm')->getRepository(AdminUser::class);
    }
    
    /**
     * Get the password reset token repository
     */
    private function getTokenRepository()
    {
        return $this->container->get('orm')->getRepository('App\\Modules\\Admin\\Models\\PasswordResetToken');
    }
}
