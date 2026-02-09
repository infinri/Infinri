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
namespace App\Core\Console\Concerns;

/**
 * Shared console output helpers
 *
 * Used by both Command and Application to avoid duplicating
 * line(), info(), warn(), error() implementations.
 */
trait WritesOutput
{
    protected function line(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m" . PHP_EOL;
    }

    protected function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m" . PHP_EOL;
    }
}
