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
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Services\AuthManager;

/**
 * Password Controller
 * 
 * Handles password change for authenticated users.
 */
class PasswordController extends Controller
{
    use RespondsWithJson;
    protected AuthManager $auth;
    protected UserRepositoryInterface $users;
    protected PasswordHasher $hasher;

    public function __construct(
        AuthManager $auth,
        UserRepositoryInterface $users,
        PasswordHasher $hasher
    ) {
        parent::__construct();
        $this->auth = $auth;
        $this->users = $users;
        $this->hasher = $hasher;
    }

    /**
     * Show password change form
     */
    public function showChangeForm(RequestInterface $request): ResponseInterface
    {
        return $this->view('auth.password-change');
    }

    /**
     * Handle password change
     */
    public function change(RequestInterface $request): ResponseInterface
    {
        $validated = $this->validate($request, [
            'current_password' => 'required|string',
            'password' => 'required|string|min:12|confirmed',
        ]);

        $user = $this->auth->user();
        
        if (!$user instanceof AuthenticatableInterface) {
            return $this->changeFailed($request, [
                'current_password' => ['Authentication required.'],
            ]);
        }

        // Verify current password
        if (!$this->hasher->verify($validated['current_password'], $user->getAuthPassword())) {
            return $this->changeFailed($request, [
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        // Validate new password strength
        $strengthCheck = $this->hasher->validateStrength($validated['password']);
        if (!$strengthCheck['valid']) {
            return $this->changeFailed($request, [
                'password' => $strengthCheck['errors'],
            ]);
        }

        // Check if new password is different from current
        if ($this->hasher->verify($validated['password'], $user->getAuthPassword())) {
            return $this->changeFailed($request, [
                'password' => ['New password must be different from current password.'],
            ]);
        }

        // Update password
        $this->users->updatePassword($user, $this->hasher->hash($validated['password']));

        // Invalidate other sessions (security best practice)
        // TODO: Implement session invalidation

        // Regenerate session
        session()->regenerate(true);

        return $this->changeSuccess($request);
    }

    /**
     * Handle successful password change
     */
    protected function changeSuccess(RequestInterface $request): ResponseInterface
    {
        $message = 'Your password has been changed successfully.';

        if ($this->expectsJson($request)) {
            return $this->success(null, $message);
        }

        session()->flash('success', $message);
        return safe_redirect('/password/change');
    }

    /**
     * Handle failed password change
     */
    protected function changeFailed(RequestInterface $request, array $errors): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error('Password change failed.', 422, $errors);
        }

        session()->flash('errors', $errors);
        return safe_redirect('/password/change');
    }

}
