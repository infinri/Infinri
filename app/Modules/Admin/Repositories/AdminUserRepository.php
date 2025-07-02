<?php declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use App\Modules\Admin\Models\AdminUser;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;

class AdminUserRepository extends Repository
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?AdminUser
    {
        return $this->findOne([
            'email' => mb_strtolower(trim($email)),
        ]);
    }

    /**
     * Find a user by their remember token.
     */
    public function findByRememberToken(string $token): ?AdminUser
    {
        if (empty($token)) {
            return null;
        }

        return $this->findOne([
            'remember_token' => $token,
            'is_active' => true,
        ]);
    }

    /**
     * Check if an email exists (optionally excluding a user ID).
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->select()
            ->where('email', mb_strtolower(trim($email)));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() > 0;
    }

    /**
     * Get paginated list of users.
     */
    public function getPaginated(int $perPage = 15, array $filters = []): array
    {
        $select = $this->select();

        // Apply filters
        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $select->where(static function ($query) use ($search) {
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if (isset($filters['is_active'])) {
            $select->where('is_active', (bool)$filters['is_active']);
        }

        // Get total count for pagination
        $total = (clone $select)->count();

        // Apply pagination
        $page = max(1, $filters['page'] ?? 1);
        $offset = ($page - 1) * $perPage;
        
        $users = $select
            ->orderBy('name', 'ASC')
            ->offset($offset)
            ->limit($perPage)
            ->fetchAll();

        return [
            'items' => $users,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
