<?php declare(strict_types=1);

use App\Helpers\Str;

describe('Str Helper', function () {
    describe('camel()', function () {
        it('converts to camelCase', function () {
            expect(Str::camel('hello_world'))->toBe('helloWorld');
            expect(Str::camel('hello-world'))->toBe('helloWorld');
            expect(Str::camel('hello world'))->toBe('helloWorld');
        });
        
        it('handles already camelCase strings', function () {
            expect(Str::camel('helloWorld'))->toBe('helloWorld');
        });
    });
    
    describe('slug()', function () {
        it('creates URL-friendly slugs', function () {
            expect(Str::slug('Hello World'))->toBe('hello-world');
        });
        
        it('removes special characters', function () {
            expect(Str::slug('Hello @#$ World!'))->toBe('hello-world');
        });
        
        it('handles multiple spaces', function () {
            expect(Str::slug('Hello    World'))->toBe('hello-world');
        });
        
        it('trims dashes', function () {
            expect(Str::slug('  Hello World  '))->toBe('hello-world');
        });
    });
    
    describe('limit()', function () {
        it('limits string length', function () {
            $text = 'This is a long text that needs to be limited';
            $result = Str::limit($text, 20);
            
            expect($result)->toHaveLength(23); // 20 + '...'
            expect($result)->toEndWith('...');
        });
        
        it('does not limit short strings', function () {
            $text = 'Short';
            $result = Str::limit($text, 20);
            
            expect($result)->toBe('Short');
        });
        
        it('allows custom suffix', function () {
            $text = 'Long text here';
            $result = Str::limit($text, 5, '→');
            
            expect($result)->toEndWith('→');
        });
    });
    
    describe('random()', function () {
        it('generates random strings', function () {
            $random = Str::random(16);
            
            expect($random)
                ->toBeString()
                ->toHaveLength(16);
        });
        
        it('generates unique strings', function () {
            $random1 = Str::random(32);
            $random2 = Str::random(32);
            
            expect($random1)->not->toBe($random2);
        });
        
        it('uses alphanumeric characters', function () {
            $random = Str::random(100);
            
            expect($random)->toMatch('/^[a-zA-Z0-9]+$/');
        });
    });
});
