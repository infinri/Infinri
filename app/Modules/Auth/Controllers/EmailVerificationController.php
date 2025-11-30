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
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Services\AuthManager;
use App\Modules\Auth\Services\EmailVerificationService;

/**
 * Email Verification Controller
 * 
 * Handles email verification requests.
 */
class EmailVerificationController extends Controller
{
    use RespondsWithJson;

    protected AuthManager $auth;
    protected EmailVerificationService $verification;

    public function __construct(AuthManager $auth, EmailVerificationService $verification)
    {
        parent::__construct();
        $this->auth = $auth;
        $this->verification = $verification;
    }

    /**
     * Show email verification notice
     */
    public function notice(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user instanceof AuthenticatableInterface && $user->hasVerifiedEmail()) {
            return safe_redirect(config('auth.redirects.home', '/dashboard'));
        }

        return $this->view('auth.verify-email');
    }

    /**
     * Verify email using signed URL
     */
    public function verify(RequestInterface $request, string $id, string $hash): ResponseInterface
    {
        // Validate signature
        $expires = (int) $request->query('expires', '0');
        $signature = $request->query('signature', '');

        if (!$this->verification->verifySignature($id, $hash, $expires, $signature)) {
            return $this->verificationFailed($request, 'Invalid or expired verification link.');
        }

        // Verify email
        $result = $this->verification->verify($id, $hash);

        if (!$result['success']) {
            return $this->verificationFailed($request, $result['message']);
        }

        return $this->verificationSuccess($request, $result['message']);
    }

    /**
     * Resend verification email
     */
    public function resend(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        if ($user->hasVerifiedEmail()) {
            if ($this->expectsJson($request)) {
                return $this->success(null, 'Email is already verified.');
            }
            return safe_redirect(config('auth.redirects.home', '/dashboard'));
        }

        $result = $this->verification->sendVerificationEmail($user);

        if ($this->expectsJson($request)) {
            return $result['success']
                ? $this->success(null, $result['message'])
                : $this->error($result['message'], 429);
        }

        session()->flash($result['success'] ? 'status' : 'error', $result['message']);
        return safe_redirect('/email/verify');
    }

    /**
     * Handle successful verification
     */
    protected function verificationSuccess(RequestInterface $request, string $message): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->success([
                'redirect' => config('auth.redirects.home', '/dashboard'),
            ], $message);
        }

        session()->flash('success', $message);
        return safe_redirect(config('auth.redirects.home', '/dashboard'));
    }

    /**
     * Handle failed verification
     */
    protected function verificationFailed(RequestInterface $request, string $message): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error($message, 422);
        }

        session()->flash('error', $message);
        return safe_redirect('/email/verify');
    }

    /**
     * Handle unauthorized response
     */
    protected function unauthorized(RequestInterface $request): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error('Unauthorized.', 401);
        }

        return safe_redirect('/login');
    }
}
