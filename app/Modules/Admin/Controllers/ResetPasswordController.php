<?php declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AdminUser;
use App\Modules\Admin\Models\PasswordResetToken;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;

class ResetPasswordController extends BaseAdminController
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerInterface::class);
    }

    /**
     * Show the password reset form
     */
    public function showResetForm(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'] ?? '';
        
        // Find the token
        $tokenData = $this->getTokenRepository()->findByToken($token);
        
        if (!$tokenData || $tokenData->isExpired()) {
            $this->flash->addMessage('error', 'This password reset token is invalid or has expired.');
            return $this->redirect('admin.password.request');
        }
        
        return $this->view->render($response, 'admin/auth/passwords/reset', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $tokenData->email,
        ]);
    }

    /**
     * Handle the password reset request
     */
    public function reset(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $token = $data['token'] ?? '';
        $email = $data['email'] ?? '';
        
        // Validate the token
        $tokenData = $this->getTokenRepository()->findByToken($token);
        
        if (!$tokenData || $tokenData->isExpired() || $tokenData->email !== $email) {
            $this->flash->addMessage('error', 'This password reset token is invalid or has expired.');
            return $this->redirect('admin.password.request');
        }
        
        // Validate the request
        $validation = $this->validateRequest($data);
        
        if (!$validation['valid']) {
            $this->flash->addMessage('error', 'Please correct the errors below.');
            $this->flash->addMessage('form_errors', $validation['errors']);
            return $this->redirect('admin.password.reset', ['token' => $token], [
                'email' => $email,
            ]);
        }
        
        // Find the user
        $user = $this->getUserRepository()->findByEmail($email);
        
        if (!$user || !$user->isActive()) {
            $this->flash->addMessage('error', 'We can\'t find a user with that email address.');
            return $this->redirect('admin.password.request');
        }
        
        // Update the user's password
        $user->setPassword($data['password']);
        $user->save();
        
        // Delete the used token
        $this->getTokenRepository()->deleteForEmail($user->email);
        
        // Log the event
        $this->logger->info('Password reset successful', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        // Show success message and redirect to login
        $this->flash->addMessage('success', 'Your password has been reset! You can now login with your new password.');
        return $this->redirect('admin.login');
    }
    
    /**
     * Validate the password reset request
     */
    private function validateRequest(array $data): array
    {
        $errors = [];
        $valid = true;
        
        $validator = v::arrayType()
            ->key('email', v::email())
            ->key('password', v::stringType()->length(8, null))
            ->key('password_confirmation', v::equals($data['password'] ?? null));
        
        try {
            $validator->assert($data);
        } catch (\Respect\Validation\Exceptions\NestedValidationException $e) {
            $errors = $e->getMessages();
            $valid = false;
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
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
        return $this->container->get('orm')->getRepository(PasswordResetToken::class);
    }
}
