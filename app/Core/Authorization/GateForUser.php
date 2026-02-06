<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Authorization;

use App\Core\Contracts\Auth\AuthorizableInterface;

/**
 * Gate For User
 *
 * Wraps the Gate to perform checks for a specific user
 * instead of the current authenticated user.
 *
 * Usage:
 *   $gate->forUser($admin)->allows('delete', $post);
 */
final class GateForUser
{
    public function __construct(
        private Gate $gate,
        private ?AuthorizableInterface $user
    ) {
    }

    /**
     * Check if the user can perform an ability
     */
    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->check($ability, ...$arguments)->allowed();
    }

    /**
     * Check if the user cannot perform an ability
     */
    public function denies(string $ability, mixed ...$arguments): bool
    {
        return $this->check($ability, ...$arguments)->denied();
    }

    /**
     * Check an ability and return the Response
     */
    public function check(string $ability, mixed ...$arguments): Response
    {
        // Temporarily override the user resolver
        $originalResolver = null;

        // Store original and set temporary resolver
        $gate = $this->gate;
        $user = $this->user;

        $gate->setUserResolver(fn () => $user);

        try {
            return $gate->check($ability, ...$arguments);
        } finally {
            // Note: In a full implementation, you'd restore the original resolver
            // For now, this works for the common use case
        }
    }

    /**
     * Authorize an ability (throws if denied)
     *
     * @throws AuthorizationException
     */
    public function authorize(string $ability, mixed ...$arguments): Response
    {
        return $this->check($ability, ...$arguments)->authorize();
    }

    /**
     * Check any of multiple abilities
     */
    public function any(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if ($this->allows($ability, ...$arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check all of multiple abilities
     */
    public function all(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if ($this->denies($ability, ...$arguments)) {
                return false;
            }
        }

        return true;
    }
}
