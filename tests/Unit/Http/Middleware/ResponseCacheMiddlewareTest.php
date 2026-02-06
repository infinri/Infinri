<?php declare(strict_types=1);

use App\Core\Http\Middleware\ResponseCacheMiddleware;
use App\Core\Cache\ArrayStore;

describe('ResponseCacheMiddleware', function () {
    
    it('can be instantiated', function () {
        $cache = new ArrayStore();
        $middleware = new ResponseCacheMiddleware($cache);
        
        expect($middleware)->toBeInstanceOf(ResponseCacheMiddleware::class);
    });
    
    it('accepts configuration', function () {
        $cache = new ArrayStore();
        $middleware = new ResponseCacheMiddleware($cache, [
            'enabled' => true,
            'ttl' => 600,
            'prefix' => 'custom_cache:',
        ]);
        
        expect($middleware)->toBeInstanceOf(ResponseCacheMiddleware::class);
    });
    
    it('can be disabled via config', function () {
        $cache = new ArrayStore();
        $middleware = new ResponseCacheMiddleware($cache, [
            'enabled' => false,
        ]);
        
        expect($middleware)->toBeInstanceOf(ResponseCacheMiddleware::class);
    });
    
    it('has cache management methods', function () {
        $cache = new ArrayStore();
        $middleware = new ResponseCacheMiddleware($cache);
        
        expect(method_exists($middleware, 'invalidate'))->toBeTrue();
        expect(method_exists($middleware, 'flush'))->toBeTrue();
    });
    
    it('implements MiddlewareInterface', function () {
        $cache = new ArrayStore();
        $middleware = new ResponseCacheMiddleware($cache);
        
        expect($middleware)->toBeInstanceOf(\App\Core\Contracts\Http\MiddlewareInterface::class);
    });
});
