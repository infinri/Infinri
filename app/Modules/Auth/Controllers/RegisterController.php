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
use App\Modules\Auth\Controllers\Concerns\DetectsAuthContext;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Services\AuthManager;

/**
 * Register Controller
 * 
 * Handles new user registration.
 * Context-aware: registration is blocked for admin context.
 */
class RegisterController extends Controller
{
    use DetectsAuthContext;
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
     * Show registration form
     * Blocked for admin context (admins are created by other admins)
     */
    public function showRegistrationForm(RequestInterface $request): ResponseInterface
    {
        // Block registration for admin context
        if (!$this->allowsRegistration($request)) {
            return $this->registrationNotAllowed($request);
        }

        return $this->view('auth.register', $this->getContextViewData($request));
    }

    /**
     * Handle registration
     */
    public function register(RequestInterface $request): ResponseInterface
    {
        // Block registration for admin context
        if (!$this->allowsRegistration($request)) {
            return $this->registrationNotAllowed($request);
        }

        $validated = $this->validate($request, [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:12|confirmed',
        ]);

        // Validate password strength
        $strengthCheck = $this->hasher->validateStrength($validated['password']);
        if (!$strengthCheck['valid']) {
            return $this->registrationFailed($request, [
                'password' => $strengthCheck['errors'],
            ]);
        }

        // Check if email already exists
        if ($this->users->findByEmail($validated['email']) !== null) {
            return $this->registrationFailed($request, [
                'email' => ['This email is already registered.'],
            ]);
        }

        // Create user
        $user = $this->createUser($validated);

        // Auto-login after registration
        $this->auth->login($user);

        // Regenerate session
        session()->regenerate(true);

        // TODO: Send email verification notification
        // event(new Registered($user));

        return $this->registrationSuccess($request, $user);
    }

    /**
     * Create a new user
     */
    protected function createUser(array $data): User
    {
        $user = new User();
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $this->hasher->hash($data['password']),
        ]);
        $user->save();

        return $user;
    }

    /**
     * Handle successful registration
     */
    protected function registrationSuccess(RequestInterface $request, User $user): ResponseInterface
    {
        $redirect = $this->contextRedirect($request, 'home');

        if ($this->expectsJson($request)) {
            return $this->success([
                'user' => $user,
                'redirect' => $redirect,
            ], 'Registration successful.', 201);
        }

        session()->flash('success', 'Welcome! Your account has been created.');
        return safe_redirect($redirect);
    }

    /**
     * Handle failed registration
     */
    protected function registrationFailed(RequestInterface $request, array $errors): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error('Registration failed.', 422, $errors);
        }

        session()->flash('errors', $errors);
        session()->flash('old', $request->only(['name', 'email']));

        $prefix = $this->isAdminContext($request) ? '/admin' : '';
        return safe_redirect($prefix . '/register');
    }

    /**
     * Handle registration not allowed (admin context)
     */
    protected function registrationNotAllowed(RequestInterface $request): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error('Registration is not available.', 403);
        }

        session()->flash('error', 'Registration is not available for this area.');
        return safe_redirect($this->contextRedirect($request, 'login'));
    }
}
