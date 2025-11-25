<?php

declare(strict_types=1);

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
