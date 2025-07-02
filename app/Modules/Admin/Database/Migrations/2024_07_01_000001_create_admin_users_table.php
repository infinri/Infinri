<?php declare(strict_types=1);

namespace App\Modules\Admin\Database\Migrations;

use Cycle\Migrations\Migration;

class CreateAdminUsersTable extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('admin_users')
            ->addColumn('id', 'primary', ['nullable' => false, 'default' => null])
            ->addColumn('email', 'string', ['size' => 255, 'nullable' => false])
            ->addColumn('password', 'string', ['size' => 255, 'nullable' => false])
            ->addColumn('name', 'string', ['size' => 100, 'nullable' => false])
            ->addColumn('remember_token', 'string', ['size' => 100, 'nullable' => true])
            ->addColumn('is_active', 'boolean', ['default' => true, 'nullable' => false])
            ->addColumn('last_login_at', 'datetime', ['nullable' => true])
            ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => $this->now()])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['email'], ['unique' => true, 'name' => 'admin_users_email_unique'])
            ->create();
    }

    public function down(): void
    {
        $this->table('admin_users')->drop();
    }
}
