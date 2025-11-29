<?php

declare(strict_types=1);

use App\Core\Session\RedisSessionHandler;
use App\Core\Redis\RedisManager;

describe('RedisSessionHandler', function () {
    afterEach(function () {
        Mockery::close();
    });
    
    // Interface implementation tests
    it('implements SessionHandlerInterface', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler)->toBeInstanceOf(\SessionHandlerInterface::class);
    });
    
    it('implements SessionIdInterface', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler)->toBeInstanceOf(\SessionIdInterface::class);
    });
    
    it('implements SessionUpdateTimestampHandlerInterface', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler)->toBeInstanceOf(\SessionUpdateTimestampHandlerInterface::class);
    });
    
    // Constructor and configuration
    it('can be instantiated', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler)->toBeInstanceOf(RedisSessionHandler::class);
    });
    
    it('accepts custom configuration', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler(
            $manager,
            'session',
            'sess:',
            3600,
            60
        );
        
        expect($handler->getTtl())->toBe(3600);
    });
    
    // TTL methods
    it('gets TTL', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager, 'session', 'session:', 7200, 30);
        
        expect($handler->getTtl())->toBe(7200);
    });
    
    it('sets TTL', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $handler->setTtl(3600);
        
        expect($handler->getTtl())->toBe(3600);
    });
    
    // open() tests - doesn't need Redis
    it('opens session and returns true', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler->open('/tmp', 'PHPSESSID'))->toBeTrue();
    });
    
    // close() tests - doesn't need Redis
    it('closes session and returns true', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler->close())->toBeTrue();
    });
    
    // gc() tests - doesn't need Redis
    it('garbage collection returns 0', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler->gc(3600))->toBe(0);
    });
    
    // create_sid() tests - doesn't need Redis
    it('generates 64 character hex session ID', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $id = $handler->create_sid();
        
        expect($id)->toBeString();
        expect(strlen($id))->toBe(64);
        expect(ctype_xdigit($id))->toBeTrue();
    });
    
    it('generates unique session IDs', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $handler->create_sid();
        }
        
        expect(count(array_unique($ids)))->toBe(100);
    });
    
    // validateId() tests - doesn't need Redis
    it('validates correct 64 char hex ID', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $validId = str_repeat('a1b2c3d4', 8); // 64 chars
        
        expect($handler->validateId($validId))->toBeTrue();
    });
    
    it('validates lowercase hex ID', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $validId = str_repeat('abcdef01', 8);
        
        expect($handler->validateId($validId))->toBeTrue();
    });
    
    it('rejects ID that is too short', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler->validateId('short'))->toBeFalse();
    });
    
    it('rejects ID that is too long', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $longId = str_repeat('a', 65);
        
        expect($handler->validateId($longId))->toBeFalse();
    });
    
    it('rejects ID with non-hex characters', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        $invalidId = str_repeat('ghijklmn', 8);
        
        expect($handler->validateId($invalidId))->toBeFalse();
    });
    
    it('rejects empty ID', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect($handler->validateId(''))->toBeFalse();
    });
    
    // Method existence tests for Redis-dependent methods
    it('has read method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'read'))->toBeTrue();
    });
    
    it('has write method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'write'))->toBeTrue();
    });
    
    it('has destroy method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'destroy'))->toBeTrue();
    });
    
    it('has updateTimestamp method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'updateTimestamp'))->toBeTrue();
    });
    
    it('has exists method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'exists'))->toBeTrue();
    });
    
    it('has getAllSessionIds method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'getAllSessionIds'))->toBeTrue();
    });
    
    it('has getActiveSessionCount method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'getActiveSessionCount'))->toBeTrue();
    });
    
    it('has destroyAll method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'destroyAll'))->toBeTrue();
    });
    
    it('has register method', function () {
        $manager = new RedisManager();
        $handler = new RedisSessionHandler($manager);
        
        expect(method_exists($handler, 'register'))->toBeTrue();
    });
});

// Comprehensive mock-based tests for RedisSessionHandler
describe('RedisSessionHandler with mocks', function () {
    beforeEach(function () {
        \App\Core\Application::resetInstance();
        $this->app = new \App\Core\Application(BASE_PATH);
        $this->app->bootstrap();
        
        $this->mockRedis = Mockery::mock(\Redis::class);
        $this->mockManager = Mockery::mock(RedisManager::class);
        $this->mockManager->shouldReceive('connection')
            ->andReturn($this->mockRedis)
            ->byDefault();
    });
    
    afterEach(function () {
        \App\Core\Application::resetInstance();
        Mockery::close();
    });
    
    it('reads session data successfully', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        $sessionData = 'serialized_session_data';
        
        $this->mockRedis->shouldReceive('set')
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->with("session:{$sessionId}")
            ->andReturn($sessionData);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $data = $handler->read($sessionId);
        
        expect($data)->toBe($sessionData);
    });
    
    it('returns empty string when session not found', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('set')
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->andReturn(false);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $data = $handler->read($sessionId);
        
        expect($data)->toBe('');
    });
    
    it('returns empty string on read error', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('set')
            ->andReturn(false);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->andThrow(new \RedisException('Read error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $data = $handler->read($sessionId);
        
        expect($data)->toBe('');
    });
    
    it('writes session data successfully', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        $sessionData = 'new_session_data';
        
        $this->mockRedis->shouldReceive('setex')
            ->once()
            ->with("session:{$sessionId}", 7200, $sessionData)
            ->andReturn(true);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->write($sessionId, $sessionData);
        
        expect($result)->toBeTrue();
    });
    
    it('returns false on write error', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('setex')
            ->once()
            ->andThrow(new \RedisException('Write error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->write($sessionId, 'data');
        
        expect($result)->toBeFalse();
    });
    
    it('destroys session successfully', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('get')
            ->andReturn(null);
        $this->mockRedis->shouldReceive('del')
            ->atLeast()->once()
            ->andReturn(1);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->destroy($sessionId);
        
        expect($result)->toBeTrue();
    });
    
    it('returns false on destroy error', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('get')
            ->andReturn(null);
        $this->mockRedis->shouldReceive('del')
            ->once()
            ->andThrow(new \RedisException('Destroy error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->destroy($sessionId);
        
        expect($result)->toBeFalse();
    });
    
    it('updates timestamp successfully', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('expire')
            ->once()
            ->with("session:{$sessionId}", 7200)
            ->andReturn(true);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->updateTimestamp($sessionId, 'data');
        
        expect($result)->toBeTrue();
    });
    
    it('returns false on timestamp update error', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('expire')
            ->once()
            ->andThrow(new \RedisException('Expire error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->updateTimestamp($sessionId, 'data');
        
        expect($result)->toBeFalse();
    });
    
    it('checks session exists - true', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('exists')
            ->once()
            ->with("session:{$sessionId}")
            ->andReturn(1);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->exists($sessionId);
        
        expect($result)->toBeTrue();
    });
    
    it('checks session exists - false', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('exists')
            ->once()
            ->andReturn(0);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->exists($sessionId);
        
        expect($result)->toBeFalse();
    });
    
    it('returns false on exists error', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('exists')
            ->once()
            ->andThrow(new \RedisException('Exists error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $result = $handler->exists($sessionId);
        
        expect($result)->toBeFalse();
    });
    
    it('gets all session IDs', function () {
        $keys = [
            'session:' . str_repeat('a', 64),
            'session:' . str_repeat('b', 64),
            'session:lock:' . str_repeat('c', 64), // should be filtered
        ];
        
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->with('session:*')
            ->andReturn($keys);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $ids = $handler->getAllSessionIds();
        
        expect($ids)->toBeArray();
        expect(count($ids))->toBe(2);
    });
    
    it('returns empty array on getAllSessionIds error', function () {
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->andThrow(new \RedisException('Keys error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $ids = $handler->getAllSessionIds();
        
        expect($ids)->toBeArray();
        expect($ids)->toBeEmpty();
    });
    
    it('gets active session count', function () {
        $keys = [
            'session:' . str_repeat('a', 64),
            'session:' . str_repeat('b', 64),
        ];
        
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->andReturn($keys);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $count = $handler->getActiveSessionCount();
        
        expect($count)->toBe(2);
    });
    
    it('destroys all sessions', function () {
        $keys = [
            'session:' . str_repeat('a', 64),
            'session:' . str_repeat('b', 64),
        ];
        
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->andReturn($keys);
        $this->mockRedis->shouldReceive('del')
            ->once()
            ->andReturn(2);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $count = $handler->destroyAll();
        
        expect($count)->toBe(2);
    });
    
    it('returns 0 when no sessions to destroy', function () {
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->andReturn([]);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $count = $handler->destroyAll();
        
        expect($count)->toBe(0);
    });
    
    it('returns 0 on destroyAll error', function () {
        $this->mockRedis->shouldReceive('keys')
            ->once()
            ->andThrow(new \RedisException('Keys error'));
        
        $handler = new RedisSessionHandler($this->mockManager);
        $count = $handler->destroyAll();
        
        expect($count)->toBe(0);
    });
    
    it('acquires lock successfully', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        $sessionData = 'data';
        
        $this->mockRedis->shouldReceive('set')
            ->once()
            ->with("session:lock:{$sessionId}", Mockery::type('string'), ['NX', 'EX' => 30])
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->andReturn($sessionData);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $data = $handler->read($sessionId);
        
        expect($data)->toBe($sessionData);
    });
    
    it('retries lock acquisition', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        // First attempt fails, second succeeds
        $this->mockRedis->shouldReceive('set')
            ->once()
            ->andReturn(false);
        $this->mockRedis->shouldReceive('set')
            ->once()
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->andReturn('data');
        
        $handler = new RedisSessionHandler($this->mockManager, 'session', 'session:', 7200, 1);
        $data = $handler->read($sessionId);
        
        expect($data)->toBe('data');
    });
    
    it('releases lock on close', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('set')
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->andReturn('data', getmypid() . ':' . time());
        $this->mockRedis->shouldReceive('del')
            ->once()
            ->andReturn(1);
        
        $handler = new RedisSessionHandler($this->mockManager);
        $handler->read($sessionId);
        $result = $handler->close();
        
        expect($result)->toBeTrue();
    });
    
    it('uses custom prefix', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('set')
            ->andReturn(true);
        $this->mockRedis->shouldReceive('get')
            ->once()
            ->with("custom:{$sessionId}")
            ->andReturn('data');
        
        $handler = new RedisSessionHandler($this->mockManager, 'session', 'custom:');
        $data = $handler->read($sessionId);
        
        expect($data)->toBe('data');
    });
    
    it('uses custom TTL for write', function () {
        $sessionId = str_repeat('a1b2c3d4', 8);
        
        $this->mockRedis->shouldReceive('setex')
            ->once()
            ->with("session:{$sessionId}", 3600, 'data')
            ->andReturn(true);
        
        $handler = new RedisSessionHandler($this->mockManager, 'session', 'session:', 3600);
        $result = $handler->write($sessionId, 'data');
        
        expect($result)->toBeTrue();
    });
});
