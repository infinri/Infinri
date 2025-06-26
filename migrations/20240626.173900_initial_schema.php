<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

class OrmDefault20240626173900_initial_schema extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->database()->execute(
            "CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL,
                CONSTRAINT users_email_unique UNIQUE (email)
            )"
        );
    }

    public function down(): void
    {
        $this->database()->execute('DROP TABLE IF EXISTS users');
    }
}
