<?php declare(strict_types=1);

use App\Helpers\Env;

describe('Env Helper', function () {
    beforeEach(function () {
        // Set up test environment variables
        $_ENV['TEST_STRING'] = 'hello';
        $_ENV['TEST_INT'] = '123';
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';
        $_ENV['TEST_EMPTY'] = '';
    });
    
    describe('get()', function () {
        it('returns string values', function () {
            expect(Env::get('TEST_STRING'))->toBe('hello');
        });
        
        it('casts to int when type specified', function () {
            expect(Env::get('TEST_INT', null, 'int'))->toBe(123);
        });
        
        it('casts to bool when type specified', function () {
            expect(Env::get('TEST_BOOL_TRUE', null, 'bool'))->toBeTrue();
            expect(Env::get('TEST_BOOL_FALSE', null, 'bool'))->toBeFalse();
        });
        
        it('casts to float when type specified', function () {
            $_ENV['TEST_FLOAT'] = '3.14';
            expect(Env::get('TEST_FLOAT', null, 'float'))->toBe(3.14);
        });
        
        it('casts to array when type specified', function () {
            $_ENV['TEST_ARRAY'] = 'a,b,c';
            expect(Env::get('TEST_ARRAY', null, 'array'))->toBe(['a', 'b', 'c']);
        });
        
        it('returns default for missing keys', function () {
            expect(Env::get('MISSING_KEY', 'default'))->toBe('default');
        });
        
        it('handles empty string values', function () {
            expect(Env::get('TEST_EMPTY'))->toBe('');
        });
        
        it('throws on invalid type parameter', function () {
            expect(fn() => Env::get('TEST_STRING', null, 'invalid'))
                ->toThrow(InvalidArgumentException::class);
        });
    });
    
    describe('require()', function () {
        it('returns value when key exists', function () {
            expect(Env::require('TEST_STRING'))->toBe('hello');
        });
        
        it('throws when key missing', function () {
            expect(fn() => Env::require('MISSING_REQUIRED_KEY'))
                ->toThrow(RuntimeException::class, 'Required environment variable');
        });
    });
});
