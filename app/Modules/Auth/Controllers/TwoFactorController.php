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
use App\Modules\Auth\Security\PasswordHasher;
use App\Modules\Auth\Services\AuthManager;
use App\Modules\Auth\Services\TwoFactorService;

/**
 * Two-Factor Controller
 * 
 * Handles 2FA enable/disable and recovery code management.
 */
class TwoFactorController extends Controller
{
    use RespondsWithJson;

    protected AuthManager $auth;
    protected TwoFactorService $twoFactor;
    protected PasswordHasher $hasher;

    public function __construct(
        AuthManager $auth,
        TwoFactorService $twoFactor,
        PasswordHasher $hasher
    ) {
        parent::__construct();
        $this->auth = $auth;
        $this->twoFactor = $twoFactor;
        $this->hasher = $hasher;
    }

    /**
     * Show 2FA settings page
     */
    public function show(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        $enabled = $user->hasTwoFactorEnabled();
        $recoveryCodesRemaining = $enabled 
            ? $this->twoFactor->getRemainingRecoveryCodeCount($user) 
            : 0;

        if ($this->expectsJson($request)) {
            return $this->success([
                'enabled' => $enabled,
                'recovery_codes_remaining' => $recoveryCodesRemaining,
            ]);
        }

        return $this->view('auth.two-factor.index', [
            'enabled' => $enabled,
            'recoveryCodesRemaining' => $recoveryCodesRemaining,
        ]);
    }

    /**
     * Generate 2FA setup data (secret + QR code)
     */
    public function setup(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        // Require password confirmation
        $validated = $this->validate($request, [
            'password' => 'required|string',
        ]);

        if (!$this->hasher->verify($validated['password'], $user->getAuthPassword())) {
            return $this->error('Password is incorrect.', 422, [
                'password' => ['Password is incorrect.'],
            ]);
        }

        $setupData = $this->twoFactor->generateSecret($user);

        // Store pending setup in session
        session()->set('2fa.setup_secret', $setupData['secret']);
        session()->set('2fa.setup_recovery_codes', $setupData['recovery_codes']);

        if ($this->expectsJson($request)) {
            return $this->success([
                'qr_url' => $setupData['qr_url'],
                'secret' => $setupData['secret'],
                'recovery_codes' => $setupData['recovery_codes'],
            ], 'Scan the QR code with your authenticator app.');
        }

        return $this->view('auth.two-factor.setup', [
            'qrUrl' => $setupData['qr_url'],
            'secret' => $setupData['secret'],
            'recoveryCodes' => $setupData['recovery_codes'],
        ]);
    }

    /**
     * Enable 2FA after verifying code
     */
    public function enable(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        $validated = $this->validate($request, [
            'code' => 'required|string',
        ]);

        $secret = session('2fa.setup_secret');
        $recoveryCodes = session('2fa.setup_recovery_codes');

        if ($secret === null || $recoveryCodes === null) {
            return $this->error('Please start the 2FA setup process first.', 422);
        }

        if (!$this->twoFactor->enable($user, $secret, $validated['code'], $recoveryCodes)) {
            return $this->error('Invalid verification code.', 422, [
                'code' => ['Invalid verification code.'],
            ]);
        }

        // Clear setup session
        session()->forget('2fa.setup_secret');
        session()->forget('2fa.setup_recovery_codes');

        if ($this->expectsJson($request)) {
            return $this->success(null, 'Two-factor authentication enabled successfully.');
        }

        session()->flash('success', 'Two-factor authentication enabled successfully.');
        return safe_redirect('/two-factor');
    }

    /**
     * Disable 2FA
     */
    public function disable(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        // Require password confirmation
        $validated = $this->validate($request, [
            'password' => 'required|string',
        ]);

        if (!$this->hasher->verify($validated['password'], $user->getAuthPassword())) {
            return $this->error('Password is incorrect.', 422, [
                'password' => ['Password is incorrect.'],
            ]);
        }

        $this->twoFactor->disable($user);

        if ($this->expectsJson($request)) {
            return $this->success(null, 'Two-factor authentication disabled.');
        }

        session()->flash('success', 'Two-factor authentication disabled.');
        return safe_redirect('/two-factor');
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(RequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof AuthenticatableInterface) {
            return $this->unauthorized($request);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return $this->error('Two-factor authentication is not enabled.', 422);
        }

        // Require password confirmation
        $validated = $this->validate($request, [
            'password' => 'required|string',
        ]);

        if (!$this->hasher->verify($validated['password'], $user->getAuthPassword())) {
            return $this->error('Password is incorrect.', 422, [
                'password' => ['Password is incorrect.'],
            ]);
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        if ($this->expectsJson($request)) {
            return $this->success([
                'recovery_codes' => $codes,
            ], 'Recovery codes regenerated. Save these codes securely.');
        }

        return $this->view('auth.two-factor.recovery-codes', [
            'recoveryCodes' => $codes,
        ]);
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
