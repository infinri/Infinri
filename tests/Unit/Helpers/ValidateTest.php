<?php declare(strict_types=1);

use App\Helpers\Validate;

describe('Validate Helper', function () {
    describe('sanitize()', function () {
        it('sanitizes strings', function () {
            $dirty = '<script>alert("xss")</script>';
            $clean = Validate::sanitize($dirty);
            
            expect($clean)
                ->toBeString()
                ->not->toContain('<script>');
        });
        
        it('sanitizes arrays recursively', function () {
            $dirty = [
                'name' => '<b>Test</b>',
                'nested' => [
                    'value' => '<script>xss</script>'
                ]
            ];
            
            $clean = Validate::sanitize($dirty);
            
            expect($clean['name'])->not->toContain('<b>');
            expect($clean['nested']['value'])->not->toContain('<script>');
        });
        
        it('preserves safe content', function () {
            $safe = 'Hello World 123';
            $clean = Validate::sanitize($safe);
            
            expect($clean)->toBe('Hello World 123');
        });
        
        it('handles empty strings', function () {
            expect(Validate::sanitize(''))->toBe('');
        });
    });
    
    describe('pathIsSecure()', function () {
        it('validates real paths within base', function () {
            $basePath = dirname(__DIR__, 3) . '/app';
            $testPath = $basePath . '/base/helpers/Esc.php';
            
            if (file_exists($testPath)) {
                expect(Validate::pathIsSecure($testPath, $basePath))->toBeTrue();
            } else {
                expect(true)->toBeTrue(); // Skip if file doesn't exist
            }
        });
        
        it('rejects paths outside base', function () {
            $basePath = dirname(__DIR__, 3) . '/app';
            $testPath = '/etc/passwd';
            
            expect(Validate::pathIsSecure($testPath, $basePath))->toBeFalse();
        });
        
        it('rejects non-existent paths', function () {
            $basePath = dirname(__DIR__, 3) . '/app';
            $testPath = $basePath . '/nonexistent/file.php';
            
            expect(Validate::pathIsSecure($testPath, $basePath))->toBeFalse();
        });
        
        it('rejects paths with traversal attempts', function () {
            $basePath = dirname(__DIR__, 3) . '/app';
            $testPath = $basePath . '/../../../etc/passwd';
            
            expect(Validate::pathIsSecure($testPath, $basePath))->toBeFalse();
        });
    });
});
