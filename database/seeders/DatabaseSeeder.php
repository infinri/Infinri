<?php

declare(strict_types=1);

use App\Core\Database\Seeder;

/**
 * Database Seeder
 * 
 * Main seeder that calls all other seeders.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PageSeeder::class);
    }
}
