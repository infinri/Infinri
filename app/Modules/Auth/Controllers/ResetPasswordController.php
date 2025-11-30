<?php

declare(strict_types=1);

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
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Services\AuthManager;
use App\Modules\Auth\Services\PasswordResetService;

/**
 * Reset Password Controller
 * 
 * Handles password reset using token.
 */
class ResetPasswordController extends Controller
{
    use RespondsWithJson;
    protected AuthManager $auth;
    protected PasswordResetService $passwordReset;
    protected PasswordHasher $hasher;

    public function __construct(
        AuthManager $auth,
        PasswordResetService $passwordReset,
        PasswordHasher $hasher
    ) {
        parent::__construct();
        $this->auth = $auth;
        $this->passwordReset = $passwordReset;
        $this->hasher = $hasher;
    }

    /**
     * Show password reset form
     */
    public function showResetForm(RequestInterface $request, string $token): ResponseInterface
    {
        return $this->view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle password reset
     */
    public function reset(RequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($request, [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:12|confirmed',
        ]);

        // Validate password strength
        $strengthCheck = $this->hasher->validateStrength($validated['password']);
        if (!$strengthCheck['valid']) {
            return $this->resetFailed($request, [
                'password' => $strengthCheck['errors'],
            ]);
        }

        // Verify token and reset password
        $result = $this->passwordReset->reset(
            $validated['email'],
            $validated['token'],
            $validated['password']
        );

        if (!$result['success']) {
            return $this->resetFailed($request, [
                'email' => [$result['message'] ?? 'Invalid or expired reset token.'],
            ]);
        }

        // Auto-login after password reset
        if ($result['user'] ?? null) {
            $this->auth->login($result['user']);
            session()->regenerate(true);
        }

        return $this->resetSuccess($request);
    }

    /**
     * Handle successful reset
     */
    protected function resetSuccess(RequestInterface $request): ResponseInterface
    {
        $message = 'Your password has been reset successfully.';

        if ($this->expectsJson($request)) {
            return $this->success([
                'redirect' => config('auth.redirects.home', '/dashboard'),
            ], $message);
        }

        session()->flash('success', $message);
        return safe_redirect(config('auth.redirects.home', '/dashboard'));
    }

    /**
     * Handle failed reset
     */
    protected function resetFailed(RequestInterface $request, array $errors): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error('Password reset failed.', 422, $errors);
        }

        session()->flash('errors', $errors);
        return safe_redirect('/forgot-password');
    }

}
