<?php declare(strict_types=1);

namespace TestModule;

use App\Modules\Module as BaseModule;
use App\Modules\ModuleInterface;
use Psr\Container\ContainerInterface;

class TestModule extends BaseModule implements ModuleInterface
{
    public const ID = 'test.module';
    public const NAME = 'Test Module';
    public const VERSION = '1.0.0';
    
    private static bool $booted = false;
    private static bool $registered = false;
    
    public function register(): void
    {
        self::$registered = true;
    }
    
    public function boot(): void
    {
        self::$booted = true;
    }
    
    public function getDependencies(): array
    {
        return [];
    }
    
    public function getId(): string
    {
        return self::ID;
    }
    
    public function getName(): string
    {
        return self::NAME;
    }
    
    public function getVersion(): string
    {
        return self::VERSION;
    }
    
    public static function isRegistered(): bool
    {
        return self::$registered;
    }
    
    public static function isBooted(): bool
    {
        return self::$booted;
    }
    
    public static function reset(): void
    {
        self::$registered = false;
        self::$booted = false;
    }
}
