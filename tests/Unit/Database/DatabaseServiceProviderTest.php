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
namespace Tests\Unit\Database;

use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\DatabaseManager;
use App\Core\Container\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseServiceProviderTest extends TestCase
{
    #[Test]
    public function extends_service_provider(): void
    {
        // Use reflection to check class hierarchy without instantiating
        $reflection = new \ReflectionClass(DatabaseServiceProvider::class);
        
        $this->assertTrue($reflection->isSubclassOf(ServiceProvider::class));
    }

    #[Test]
    public function has_register_method(): void
    {
        $reflection = new \ReflectionClass(DatabaseServiceProvider::class);
        
        $this->assertTrue($reflection->hasMethod('register'));
    }

    #[Test]
    public function has_boot_method(): void
    {
        $reflection = new \ReflectionClass(DatabaseServiceProvider::class);
        
        $this->assertTrue($reflection->hasMethod('boot'));
    }

    #[Test]
    public function register_method_is_public(): void
    {
        $reflection = new \ReflectionClass(DatabaseServiceProvider::class);
        $method = $reflection->getMethod('register');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function boot_method_is_public(): void
    {
        $reflection = new \ReflectionClass(DatabaseServiceProvider::class);
        $method = $reflection->getMethod('boot');
        
        $this->assertTrue($method->isPublic());
    }

}
