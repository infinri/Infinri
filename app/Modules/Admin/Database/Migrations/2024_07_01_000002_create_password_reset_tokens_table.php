<?php declare(strict_types=1);

namespace App\Modules\Admin\Database\Migrations;

use Cycle\Migrations\Migration;

class CreatePasswordResetTokensTable extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('password_reset_tokens')
            ->addColumn('id', 'primary', ['nullable' => false, 'default' => null])
            ->addColumn('email', 'string', ['size' => 255, 'nullable' => false])
            ->addColumn('token', 'string', ['size' => 255, 'nullable' => false])
            ->addColumn('created_at', 'datetime', ['nullable' => false, 'defaultValue' => $this->now()])
            ->addColumn('expires_at', 'datetime', ['nullable' => false])
            ->addIndex(['email'], ['unique' => true, 'name' => 'password_reset_tokens_email_unique'])
            ->addIndex(['token'], ['unique' => true, 'name' => 'password_reset_tokens_token_unique'])
            ->create();
    }

    public function down(): void
    {
        $this->table('password_reset_tokens')->drop();
    }
}
