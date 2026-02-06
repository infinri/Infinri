<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Database;

/**
 * Migration State Manager
 *
 * Tracks migration state and marks system as "unsafe" if migrations fail.
 * Helps prevent running the app in a corrupted database state.
 */
class MigrationState
{
    protected string $statePath;
    protected array $state = [];

    public function __construct(?string $statePath = null)
    {
        $this->statePath = $statePath ?? $this->getDefaultStatePath();
        $this->load();
    }

    /**
     * Mark migration as started
     */
    public function markStarted(string $migration): void
    {
        $this->state['current'] = $migration;
        $this->state['status'] = 'running';
        $this->state['started_at'] = date('c');
        $this->state['error'] = null;
        $this->save();
    }

    /**
     * Mark migration as completed
     */
    public function markCompleted(string $migration): void
    {
        if (($this->state['current'] ?? null) === $migration) {
            $this->state['current'] = null;
            $this->state['status'] = 'idle';
            $this->state['last_completed'] = $migration;
            $this->state['completed_at'] = date('c');
        }
        $this->save();
    }

    /**
     * Mark migration as failed
     */
    public function markFailed(string $migration, string $error): void
    {
        $this->state['current'] = $migration;
        $this->state['status'] = 'failed';
        $this->state['error'] = $error;
        $this->state['failed_at'] = date('c');
        $this->save();
    }

    /**
     * Clear failed state (after manual recovery)
     */
    public function clearFailed(): void
    {
        $this->state['status'] = 'idle';
        $this->state['current'] = null;
        $this->state['error'] = null;
        $this->state['cleared_at'] = date('c');
        $this->save();
    }

    /**
     * Check if system is safe (no failed migrations)
     */
    public function isSafe(): bool
    {
        return ($this->state['status'] ?? 'idle') !== 'failed';
    }

    /**
     * Check if migrations are currently running
     */
    public function isRunning(): bool
    {
        return ($this->state['status'] ?? 'idle') === 'running';
    }

    /**
     * Get current state
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Get failed migration info
     */
    public function getFailedInfo(): ?array
    {
        if ($this->isSafe()) {
            return null;
        }

        return [
            'migration' => $this->state['current'] ?? 'unknown',
            'error' => $this->state['error'] ?? 'Unknown error',
            'failed_at' => $this->state['failed_at'] ?? null,
        ];
    }

    protected function load(): void
    {
        if (file_exists($this->statePath)) {
            $this->state = require $this->statePath;
        } else {
            $this->state = [
                'status' => 'idle',
                'current' => null,
                'error' => null,
            ];
        }
    }

    protected function save(): void
    {
        save_php_array($this->statePath, $this->state, 'Migration State');
    }

    protected function getDefaultStatePath(): string
    {
        return base_path('var/state/migrations.php');
    }
}
