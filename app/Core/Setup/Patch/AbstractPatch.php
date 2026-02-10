<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */
namespace App\Core\Setup\Patch;

use App\Core\Contracts\Database\ConnectionInterface;

/**
 * Abstract Patch
 *
 * Base class for data and schema patches providing common functionality.
 */
abstract class AbstractPatch
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get patches that this patch depends on
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases for this patch
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Helper: Insert data if not exists
     */
    protected function insertIfNotExists(string $table, array $data, string $uniqueColumn): bool
    {
        $exists = $this->connection->selectOne(
            "SELECT 1 FROM \"{$table}\" WHERE \"{$uniqueColumn}\" = ? LIMIT 1",
            [$data[$uniqueColumn]]
        );

        if ($exists !== null) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $this->connection->insert(
            sprintf(
                'INSERT INTO "%s" ("%s") VALUES (%s)',
                $table,
                implode('", "', $columns),
                implode(', ', $placeholders)
            ),
            array_values($data)
        );

        return true;
    }

    /**
     * Helper: Get current timestamp
     */
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
