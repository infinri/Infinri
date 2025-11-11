<?php declare(strict_types=1);

use App\Helpers\Path;

describe('Path Helper', function () {
    describe('normalize()', function () {
        it('rejects null bytes', function () {
            expect(fn() => Path::normalize("test\0file"))
                ->toThrow(InvalidArgumentException::class, 'Invalid path');
        });
        
        it('rejects path traversal attempts', function () {
            expect(fn() => Path::normalize('../../../etc/passwd'))
                ->toThrow(InvalidArgumentException::class, 'Invalid path');
        });
        
        it('normalizes valid paths', function () {
            $path = Path::normalize('test/path');
            
            expect($path)->toBeString()->toContain('test');
        });
        
        it('accepts empty strings', function () {
            // Empty string is normalized to empty string
            expect(Path::normalize(''))->toBe('');
        });
    });
    
    describe('join()', function () {
        it('joins path segments', function () {
            $path = Path::join('app', 'modules', 'home');
            
            expect($path)->toBe('app/modules/home');
        });
        
        it('handles single segment', function () {
            $path = Path::join('app');
            
            expect($path)->toBe('app');
        });
        
        it('removes duplicate slashes', function () {
            $path = Path::join('app/', '/modules/', '/home');
            
            expect($path)->not->toContain('//');
        });
    });
    
    describe('module()', function () {
        it('validates module names', function () {
            expect(fn() => Path::module('home'))
                ->not->toThrow(Exception::class);
        });
        
        it('rejects invalid module names', function () {
            expect(fn() => Path::module('../../etc'))
                ->toThrow(InvalidArgumentException::class);
        });
        
        it('rejects uppercase in module names', function () {
            expect(fn() => Path::module('Home'))
                ->toThrow(InvalidArgumentException::class);
        });
        
        it('accepts optional file parameter', function () {
            $path = Path::module('home', 'index.php');
            
            expect($path)
                ->toBeString()
                ->toContain('modules/home/index.php');
        });
        
        it('rejects file path with traversal', function () {
            expect(fn() => Path::module('home', '../../../etc/passwd'))
                ->toThrow(InvalidArgumentException::class);
        });
        
        it('rejects file path with null byte', function () {
            expect(fn() => Path::module('home', "test\0file"))
                ->toThrow(InvalidArgumentException::class);
        });
    });
    
    describe('scanDir()', function () {
        it('returns empty array for non-existent directory', function () {
            $result = Path::scanDir('/nonexistent/directory');
            
            expect($result)->toBe([]);
        });
        
        it('scans existing directories', function () {
            // Use the app directory which should exist
            $appDir = dirname(__DIR__, 3) . '/app';
            $result = Path::scanDir($appDir);
            
            expect($result)->toBeArray();
            // Should contain base, modules, etc.
            expect($result)->toContain('base');
        });
        
        it('excludes dot directories', function () {
            $appDir = dirname(__DIR__, 3) . '/app';
            $result = Path::scanDir($appDir);
            
            expect($result)->not->toContain('.');
            expect($result)->not->toContain('..');
        });
    });
});
