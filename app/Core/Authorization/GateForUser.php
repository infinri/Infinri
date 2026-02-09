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
        $gate = $this->gate;
        $user = $this->user;

        // Save the original resolver before overriding
        $originalResolver = $gate->getUserResolver();
        $gate->setUserResolver(fn () => $user);

        try {
            return $gate->check($ability, ...$arguments);
        } finally {
            // Restore the original resolver so shared Gate instance isn't corrupted
            $gate->setUserResolver($originalResolver);
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
