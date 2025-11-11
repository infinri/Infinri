<?php declare(strict_types=1);

use App\Helpers\Esc;

describe('Esc Helper', function () {
    describe('html()', function () {
        it('escapes HTML special characters', function () {
            $input = '<script>alert("XSS")</script>';
            $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
            
            expect(Esc::html($input))->toBe($expected);
        });
        
        it('escapes quotes for attributes', function () {
            $input = 'value with "quotes" and \'apostrophes\'';
            $output = Esc::html($input);
            
            expect($output)
                ->toContain('&quot;')
                ->toContain('&#039;');
        });
        
        it('handles empty strings', function () {
            expect(Esc::html(''))->toBe('');
        });
        
        it('handles already encoded entities', function () {
            $input = '&lt;div&gt;';
            $expected = '&amp;lt;div&amp;gt;';
            
            expect(Esc::html($input))->toBe($expected);
        });
    });
    
    describe('url()', function () {
        it('encodes URL parameters', function () {
            $input = 'hello world';
            $expected = 'hello%20world';
            
            expect(Esc::url($input))->toBe($expected);
        });
        
        it('encodes special characters', function () {
            $input = 'email@example.com';
            $expected = 'email%40example.com';
            
            expect(Esc::url($input))->toBe($expected);
        });
        
        it('handles empty strings', function () {
            expect(Esc::url(''))->toBe('');
        });
        
        it('encodes forward slashes', function () {
            $input = 'path/to/file';
            $expected = 'path%2Fto%2Ffile';
            
            expect(Esc::url($input))->toBe($expected);
        });
    });
    
    describe('js()', function () {
        it('encodes strings for JavaScript context', function () {
            $input = 'Hello "World"';
            $output = Esc::js($input);
            
            expect($output)
                ->toBeString()
                ->toContain('Hello')
                ->toContain('World');
        });
        
        it('escapes HTML tags in JSON', function () {
            $input = '<script>alert("XSS")</script>';
            $output = Esc::js($input);
            
            expect($output)
                ->not->toContain('<script>')
                ->toContain('\\u003C');
        });
        
        it('handles quotes safely', function () {
            $input = "It's a \"test\"";
            $output = Esc::js($input);
            
            expect($output)
                ->toBeString()
                ->toContain('test');
        });
        
        it('throws on invalid UTF-8', function () {
            expect(fn() => Esc::js("\xB1\x31"))
                ->toThrow(JsonException::class);
        });
    });
});
