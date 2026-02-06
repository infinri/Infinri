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
use App\Core\Security\SafeRedirect;
use App\Modules\Auth\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Controllers\Concerns\DetectsAuthContext;
use App\Modules\Auth\Controllers\Concerns\RespondsWithJson;
use App\Modules\Auth\Services\AuthManager;
use App\Modules\Auth\Services\TwoFactorService;

/**
 * Two-Factor Challenge Controller
 * 
 * Handles 2FA verification during login.
 */
class TwoFactorChallengeController extends Controller
{
    use DetectsAuthContext;
    use RespondsWithJson;

    protected AuthManager $auth;
    protected TwoFactorService $twoFactor;
    protected UserRepositoryInterface $users;

    public function __construct(
        AuthManager $auth,
        TwoFactorService $twoFactor,
        UserRepositoryInterface $users
    ) {
        parent::__construct();
        $this->auth = $auth;
        $this->twoFactor = $twoFactor;
        $this->users = $users;
    }

    /**
     * Show 2FA challenge form
     */
    public function showChallengeForm(RequestInterface $request): ResponseInterface
    {
        // Ensure user is in 2FA challenge state
        if (!$this->hasPendingChallenge()) {
            return safe_redirect($this->contextRedirect($request, 'login'));
        }

        return $this->view('auth.two-factor.challenge', $this->getContextViewData($request));
    }

    /**
     * Verify 2FA code
     */
    public function verify(RequestInterface $request): ResponseInterface
    {
        if (!$this->hasPendingChallenge()) {
            return $this->challengeFailed($request, 'No pending 2FA challenge.');
        }

        $validated = $this->validate($request, [
            'code' => 'required|string',
        ]);

        $userId = session('2fa.user_id');
        $remember = (bool) session('2fa.remember', false);

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->clearChallenge();
            return $this->challengeFailed($request, 'User not found.');
        }

        $secret = $user->getTwoFactorSecret();

        if ($secret === null) {
            $this->clearChallenge();
            return $this->challengeFailed($request, '2FA is not enabled.');
        }

        // Verify TOTP code
        if (!$this->twoFactor->verify($secret, $validated['code'])) {
            return $this->challengeFailed($request, 'Invalid authentication code.');
        }

        // Clear challenge and complete login
        $this->clearChallenge();
        $this->auth->login($user, $remember);
        session()->regenerate(true);

        return $this->challengeSuccess($request);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(RequestInterface $request): ResponseInterface
    {
        if (!$this->hasPendingChallenge()) {
            return $this->challengeFailed($request, 'No pending 2FA challenge.');
        }

        $validated = $this->validate($request, [
            'recovery_code' => 'required|string',
        ]);

        $userId = session('2fa.user_id');
        $remember = (bool) session('2fa.remember', false);

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->clearChallenge();
            return $this->challengeFailed($request, 'User not found.');
        }

        // Verify recovery code
        if (!$this->twoFactor->verifyRecoveryCode($user, $validated['recovery_code'])) {
            return $this->challengeFailed($request, 'Invalid recovery code.');
        }

        // Clear challenge and complete login
        $this->clearChallenge();
        $this->auth->login($user, $remember);
        session()->regenerate(true);

        // Warn about remaining recovery codes
        $remaining = $this->twoFactor->getRemainingRecoveryCodeCount($user);
        if ($remaining <= 2) {
            session()->flash('warning', "You only have {$remaining} recovery codes remaining. Please generate new ones.");
        }

        return $this->challengeSuccess($request);
    }

    /**
     * Check if there's a pending 2FA challenge
     */
    protected function hasPendingChallenge(): bool
    {
        return session('2fa.user_id') !== null;
    }

    /**
     * Clear the 2FA challenge session data
     */
    protected function clearChallenge(): void
    {
        session()->forget('2fa.user_id');
        session()->forget('2fa.remember');
    }

    /**
     * Handle successful challenge
     */
    protected function challengeSuccess(RequestInterface $request): ResponseInterface
    {
        // Use stored context or detect from request
        $storedContext = session('2fa.context');
        $default = $storedContext 
            ? config("auth.contexts.{$storedContext}.redirects.home", '/dashboard')
            : $this->contextRedirect($request, 'home');
            
        $storedUrl = session('url.intended');
        session()->forget('url.intended');
        session()->forget('2fa.context');

        $intended = SafeRedirect::intended($storedUrl, $default);

        if ($this->expectsJson($request)) {
            return $this->success([
                'user' => $this->auth->user(),
                'redirect' => $intended,
            ], 'Two-factor authentication successful.');
        }

        return safe_redirect($intended);
    }

    /**
     * Handle failed challenge
     */
    protected function challengeFailed(RequestInterface $request, string $message): ResponseInterface
    {
        if ($this->expectsJson($request)) {
            return $this->error($message, 422, [
                'code' => [$message],
            ]);
        }

        session()->flash('error', $message);
        $prefix = $this->isAdminContext($request) ? '/admin' : '';
        return safe_redirect($prefix . '/two-factor-challenge');
    }
}
