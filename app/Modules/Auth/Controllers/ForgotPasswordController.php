<?php declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Controllers;

use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Http\Controller;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Services\PasswordResetService;

/**
 * Forgot Password Controller
 * 
 * Handles password reset link requests.
 */
class ForgotPasswordController extends Controller
{
    use RespondsWithJson;
    protected UserRepositoryInterface $users;
    protected PasswordResetService $passwordReset;

    public function __construct(
        UserRepositoryInterface $users,
        PasswordResetService $passwordReset
    ) {
        parent::__construct();
        $this->users = $users;
        $this->passwordReset = $passwordReset;
    }

    /**
     * Show forgot password form
     */
    public function showLinkRequestForm(RequestInterface $request): ResponseInterface
    {
        return $this->view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLinkEmail(RequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($request, [
            'email' => 'required|email',
        ]);

        $email = $validated['email'];

        // Always show success to prevent email enumeration
        // But only send if user exists
        $user = $this->users->findByEmail($email);
        
        if ($user !== null) {
            $this->passwordReset->sendResetLink($user);
        }

        // Uniform response regardless of user existence
        return $this->resetLinkSent($request, $email);
    }

    /**
     * Handle reset link sent response
     */
    protected function resetLinkSent(RequestInterface $request, string $email): ResponseInterface
    {
        $message = 'If an account exists for this email, you will receive a password reset link.';

        if ($this->expectsJson($request)) {
            return $this->success(['email' => $email], $message);
        }

        session()->flash('status', $message);
        return safe_redirect('/forgot-password');
    }

}
