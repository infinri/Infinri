<?php declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Contracts\Auth\AuthorizableInterface;

class MockUser implements AuthorizableInterface
{
    public function __construct(
        public int $id,
        public array $roles = [],
        public array $permissions = []
    ) {
    }

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
