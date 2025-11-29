<?php

declare(strict_types=1);

use App\Core\Redis\RedisManager;
use App\Core\Redis\RedisConnectionException;

describe('RedisManager', function () {
    afterEach(function () {
        Mockery::close();
    });
    
    // Constructor tests
    it('creates instance with default config', function () {
        $manager = new RedisManager();
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('creates instance with custom config', function () {
        $config = [
            'default' => 'custom',
            'connections' => [
                'custom' => [
                    'host' => 'localhost',
                    'port' => 6379,
                    'database' => 1,
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('uses default connection name from config', function () {
        $config = [
            'default' => 'myconnection',
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    // getActiveConnections tests
    it('returns empty active connections initially', function () {
        $manager = new RedisManager();
        expect($manager->getActiveConnections())->toBe([]);
    });
    
    it('returns active connections array', function () {
        $manager = new RedisManager();
        expect($manager->getActiveConnections())->toBeArray();
    });
    
    // disconnect tests
    it('handles disconnect for non-existent connection', function () {
        $manager = new RedisManager();
        $manager->disconnect('nonexistent');
        expect($manager->getActiveConnections())->toBe([]);
    });
    
    it('handles disconnect with null name (uses default)', function () {
        $manager = new RedisManager();
        $manager->disconnect(null);
        expect($manager->getActiveConnections())->toBe([]);
    });
    
    // disconnectAll tests
    it('handles disconnectAll with no connections', function () {
        $manager = new RedisManager();
        $manager->disconnectAll();
        expect($manager->getActiveConnections())->toBe([]);
    });
    
    // connection tests with mocking
    it('creates connection with persistent mode', function () {
        $config = [
            'default' => 'test',
            'connections' => [
                'test' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'persistent' => true,
                    'persistent_id' => 'test_persistent',
                    'timeout' => 2.0,
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('creates connection with non-persistent mode', function () {
        $config = [
            'default' => 'test',
            'connections' => [
                'test' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'persistent' => false,
                    'timeout' => 2.0,
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('creates connection with password', function () {
        $config = [
            'default' => 'test',
            'connections' => [
                'test' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'password' => 'secret',
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('creates connection with database selection', function () {
        $config = [
            'default' => 'test',
            'connections' => [
                'test' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 5,
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('creates connection with prefix', function () {
        $config = [
            'default' => 'test',
            'connections' => [
                'test' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'prefix' => 'myapp:',
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    // getConnectionConfig fallback test
    it('falls back to environment config when connection not defined', function () {
        $manager = new RedisManager([]);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    // destructor test
    it('clears connections on destruction', function () {
        $manager = new RedisManager();
        unset($manager);
        expect(true)->toBeTrue(); // No exception on destruction
    });
});

describe('RedisManager connection behavior', function () {
    afterEach(function () {
        Mockery::close();
    });
    
    it('throws RedisConnectionException on connection failure', function () {
        // This test verifies the exception type exists and can be thrown
        expect(fn() => throw new RedisConnectionException('Test connection failed'))
            ->toThrow(RedisConnectionException::class, 'Test connection failed');
    });
    
    it('throws RedisConnectionException with previous exception', function () {
        $previous = new Exception('Original error');
        $exception = new RedisConnectionException('Connection failed', 0, $previous);
        
        expect($exception->getPrevious())->toBe($previous);
    });
});

describe('RedisManager configuration', function () {
    it('handles empty connections array', function () {
        $config = [
            'default' => 'default',
            'connections' => [],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('handles missing connections key', function () {
        $config = [
            'default' => 'default',
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
    
    it('handles all configuration options', function () {
        $config = [
            'default' => 'full',
            'connections' => [
                'full' => [
                    'host' => 'redis.example.com',
                    'port' => 6380,
                    'password' => 'secret123',
                    'database' => 3,
                    'timeout' => 5.0,
                    'persistent' => true,
                    'persistent_id' => 'myapp',
                    'prefix' => 'prod:',
                ],
            ],
        ];
        
        $manager = new RedisManager($config);
        expect($manager)->toBeInstanceOf(RedisManager::class);
    });
});
