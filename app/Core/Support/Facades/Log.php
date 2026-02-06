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
namespace App\Core\Support\Facades;

use App\Core\Contracts\Log\LoggerInterface;

/**
 * Log Facade
 *
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log(string $level, string $message, array $context = [])
 * @method static void setCorrelationId(string $id)
 * @method static string getCorrelationId()
 * @method static void setGlobalContext(array $context)
 * @method static void addGlobalContext(string $key, mixed $value)
 *
 * @see \App\Core\Log\Logger
 */
class Log extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return LoggerInterface::class;
    }
}
