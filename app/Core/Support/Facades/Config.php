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

use App\Core\Contracts\Config\ConfigInterface;

/**
 * Config Facade
 *
 * @method static bool has(string $key)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string|array $key, mixed $value = null)
 * @method static array all()
 * @method static void prepend(string $key, mixed $value)
 * @method static void push(string $key, mixed $value)
 *
 * @see \App\Core\Config\Config
 */
class Config extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return ConfigInterface::class;
    }
}
