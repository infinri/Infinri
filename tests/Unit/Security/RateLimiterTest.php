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
namespace Tests\Unit\Security;

use App\Core\Security\RateLimiter;
use App\Core\Contracts\Cache\CacheInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private CacheInterface $cache;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->limiter = new RateLimiter($this->cache);
    }

    #[Test]
    public function hit_increments_attempts(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('rate_limit:test_key', 0)
            ->willReturn(0);

        $this->cache->expects($this->once())
            ->method('put')
            ->with('rate_limit:test_key', 1, 60);

        $result = $this->limiter->hit('test_key');
        
        $this->assertSame(1, $result);
    }

    #[Test]
    public function hit_uses_custom_decay_seconds(): void
    {
        $this->cache->method('get')->willReturn(0);

        $this->cache->expects($this->once())
            ->method('put')
            ->with('rate_limit:test_key', 1, 300);

        $this->limiter->hit('test_key', 300);
    }

    #[Test]
    public function attempts_returns_current_count(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('rate_limit:test_key', 0)
            ->willReturn(5);

        $result = $this->limiter->attempts('test_key');
        
        $this->assertSame(5, $result);
    }

    #[Test]
    public function too_many_attempts_returns_true_when_limit_reached(): void
    {
        $this->cache->method('get')->willReturn(5);

        $this->assertTrue($this->limiter->tooManyAttempts('test_key', 5));
    }

    #[Test]
    public function too_many_attempts_returns_false_when_under_limit(): void
    {
        $this->cache->method('get')->willReturn(4);

        $this->assertFalse($this->limiter->tooManyAttempts('test_key', 5));
    }

    #[Test]
    public function reset_attempts_clears_counter(): void
    {
        $this->cache->expects($this->once())
            ->method('forget')
            ->with('rate_limit:test_key')
            ->willReturn(true);

        $result = $this->limiter->resetAttempts('test_key');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function retries_left_calculates_remaining(): void
    {
        $this->cache->method('get')->willReturn(3);

        $result = $this->limiter->retriesLeft('test_key', 5);
        
        $this->assertSame(2, $result);
    }

    #[Test]
    public function retries_left_returns_zero_when_exceeded(): void
    {
        $this->cache->method('get')->willReturn(10);

        $result = $this->limiter->retriesLeft('test_key', 5);
        
        $this->assertSame(0, $result);
    }

    #[Test]
    public function clear_resets_attempts(): void
    {
        $this->cache->expects($this->once())
            ->method('forget')
            ->with('rate_limit:test_key')
            ->willReturn(true);

        $result = $this->limiter->clear('test_key');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function attempt_executes_callback_when_under_limit(): void
    {
        $this->cache->method('get')->willReturn(0);
        $this->cache->method('put')->willReturn(true);

        $executed = false;
        $result = $this->limiter->attempt('test_key', 5, function () use (&$executed) {
            $executed = true;
            return 'success';
        });

        $this->assertTrue($executed);
        $this->assertSame('success', $result);
    }

    #[Test]
    public function attempt_returns_false_when_limit_exceeded(): void
    {
        $this->cache->method('get')->willReturn(5);

        $executed = false;
        $result = $this->limiter->attempt('test_key', 5, function () use (&$executed) {
            $executed = true;
            return 'success';
        });

        $this->assertFalse($executed);
        $this->assertFalse($result);
    }
}
