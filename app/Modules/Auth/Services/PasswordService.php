<?php

declare(strict_types=1);

/**
 * Infinri Framework - Auth Module
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

namespace App\Modules\Auth\Services;

use App\Core\Contracts\Database\ConnectionInterface;
use App\Modules\Auth\Contracts\AuthenticatableInterface;
use App\Modules\Auth\Security\PasswordHasher;

/**
 * Password Service
 * 
 * Manages password storage in the `passwords` table.
 * Handles password history, expiration, and rotation.
 */
class PasswordService
{
    protected string $table = 'passwords';

    public function __construct(
        protected ConnectionInterface $db,
        protected PasswordHasher $hasher
    ) {}

    /**
     * Get the current password hash for a user
     */
    public function getCurrentHash(int|string $userId): ?string
    {
        $result = $this->db->selectOne(
            "SELECT password_hash FROM \"{$this->table}\" WHERE user_id = ? AND is_current = true LIMIT 1",
            [$userId]
        );

        return $result['password_hash'] ?? null;
    }

    /**
     * Verify a password against user's current password
     */
    public function verify(int|string $userId, string $password): bool
    {
        $hash = $this->getCurrentHash($userId);

        if ($hash === null) {
            return false;
        }

        return $this->hasher->verify($password, $hash);
    }

    /**
     * Set a new password for a user
     * 
     * - Marks all previous passwords as not current
     * - Inserts new password as current
     * - Preserves history for reuse checking
     */
    public function setPassword(int|string $userId, string $plainPassword): void
    {
        $hash = $this->hasher->hash($plainPassword);

        $this->db->transaction(function () use ($userId, $hash) {
            // Mark all existing passwords as not current
            $this->db->update(
                "UPDATE \"{$this->table}\" SET is_current = false WHERE user_id = ?",
                [$userId]
            );

            // Insert new password as current
            $this->db->insert(
                "INSERT INTO \"{$this->table}\" (user_id, password_hash, is_current, created_at, updated_at) VALUES (?, ?, true, NOW(), NOW())",
                [$userId, $hash]
            );
        });
    }

    /**
     * Check if password was previously used
     * 
     * @param int $historyCount Number of previous passwords to check
     */
    public function wasUsedBefore(int|string $userId, string $plainPassword, int $historyCount = 5): bool
    {
        $history = $this->getPasswordHistory($userId, $historyCount);

        foreach ($history as $hash) {
            if ($this->hasher->verify($plainPassword, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get password history hashes for a user
     */
    public function getPasswordHistory(int|string $userId, int $limit = 5): array
    {
        $results = $this->db->select(
            "SELECT password_hash FROM \"{$this->table}\" WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );

        return array_column($results, 'password_hash');
    }

    /**
     * Check if user's password needs to be changed
     */
    public function mustChange(int|string $userId): bool
    {
        $result = $this->db->selectOne(
            "SELECT must_change FROM \"{$this->table}\" WHERE user_id = ? AND is_current = true LIMIT 1",
            [$userId]
        );

        return (bool) ($result['must_change'] ?? false);
    }

    /**
     * Set the must_change flag
     */
    public function setMustChange(int|string $userId, bool $mustChange = true): void
    {
        $this->db->update(
            "UPDATE \"{$this->table}\" SET must_change = ? WHERE user_id = ? AND is_current = true",
            [$mustChange, $userId]
        );
    }

    /**
     * Check if user's password is expired
     */
    public function isExpired(int|string $userId): bool
    {
        $result = $this->db->selectOne(
            "SELECT expires_at FROM \"{$this->table}\" WHERE user_id = ? AND is_current = true LIMIT 1",
            [$userId]
        );

        if (empty($result['expires_at'])) {
            return false;
        }

        return strtotime($result['expires_at']) < time();
    }

    /**
     * Set password expiration
     */
    public function setExpiration(int|string $userId, ?string $expiresAt): void
    {
        $this->db->update(
            "UPDATE \"{$this->table}\" SET expires_at = ? WHERE user_id = ? AND is_current = true",
            [$expiresAt, $userId]
        );
    }

    /**
     * Check if user has a password set
     */
    public function hasPassword(int|string $userId): bool
    {
        return $this->getCurrentHash($userId) !== null;
    }

    /**
     * Delete all passwords for a user (when user is deleted)
     */
    public function deleteAll(int|string $userId): void
    {
        $this->db->delete(
            "DELETE FROM \"{$this->table}\" WHERE user_id = ?",
            [$userId]
        );
    }
}
