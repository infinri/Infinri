<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use App\Modules\Auth\Contracts\GuardInterface;
use App\Modules\Auth\Guards\SessionGuard;
use App\Modules\Auth\Guards\TokenGuard;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Guard Interface Compliance Tests
 */
class GuardInterfaceTest extends TestCase
{
    public function testSessionGuardImplementsGuardInterface(): void
    {
        $reflection = new ReflectionClass(SessionGuard::class);
        
        $this->assertTrue($reflection->implementsInterface(GuardInterface::class));
    }

    public function testTokenGuardImplementsGuardInterface(): void
    {
        $reflection = new ReflectionClass(TokenGuard::class);
        
        $this->assertTrue($reflection->implementsInterface(GuardInterface::class));
    }

    public function testGuardInterfaceHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(GuardInterface::class);
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());

        $requiredMethods = [
            'check',
            'guest',
            'user',
            'id',
            'validate',
            'attempt',
            'once',
            'login',
            'loginUsingId',
            'onceUsingId',
            'viaRemember',
            'logout',
            'getName',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertContains($method, $methods, "GuardInterface missing method: {$method}");
        }
    }

    public function testSessionGuardHasAllInterfaceMethods(): void
    {
        $interface = new ReflectionClass(GuardInterface::class);
        $guard = new ReflectionClass(SessionGuard::class);

        foreach ($interface->getMethods() as $method) {
            $this->assertTrue(
                $guard->hasMethod($method->getName()),
                "SessionGuard missing method: {$method->getName()}"
            );
        }
    }

    public function testTokenGuardHasAllInterfaceMethods(): void
    {
        $interface = new ReflectionClass(GuardInterface::class);
        $guard = new ReflectionClass(TokenGuard::class);

        foreach ($interface->getMethods() as $method) {
            $this->assertTrue(
                $guard->hasMethod($method->getName()),
                "TokenGuard missing method: {$method->getName()}"
            );
        }
    }
}
