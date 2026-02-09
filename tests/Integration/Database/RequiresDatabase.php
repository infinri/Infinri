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
namespace Tests\Integration\Database;

use PDO;
use PDOException;

trait RequiresDatabase
{
    private static bool $dbAvailable = true;

    protected function skipIfNoDB(): void
    {
        if (! self::$dbAvailable) {
            $this->markTestSkipped('PostgreSQL is not available');
        }

        try {
            new PDO(
                sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    env('DB_TEST_HOST', '127.0.0.1'),
                    (int) env('DB_TEST_PORT', 5432),
                    env('DB_TEST_DATABASE', 'infinri_test')
                ),
                env('DB_TEST_USERNAME', 'postgres'),
                env('DB_TEST_PASSWORD', 'postgres'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
            );
        } catch (PDOException) {
            self::$dbAvailable = false;
            $this->markTestSkipped('PostgreSQL is not available');
        }
    }
}
