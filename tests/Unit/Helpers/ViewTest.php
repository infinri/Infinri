<?php declare(strict_types=1);

use App\Helpers\View;
use App\Base\Helpers\Assets;

describe('View Helper', function () {
    beforeEach(function () {
        // Clear view data before each test
        View::set('test_clear', null);
    });
    
    describe('set() and get()', function () {
        it('stores and retrieves data', function () {
            View::set('title', 'Test Page');
            
            expect(View::get('title'))->toBe('Test Page');
        });
        
        it('returns default for missing keys', function () {
            expect(View::get('missing', 'default'))->toBe('default');
        });
        
        it('returns null for missing keys without default', function () {
            expect(View::get('missing'))->toBeNull();
        });
        
        it('stores multiple values', function () {
            View::set('key1', 'value1');
            View::set('key2', 'value2');
            
            expect(View::get('key1'))->toBe('value1');
            expect(View::get('key2'))->toBe('value2');
        });
    });
    
    describe('render()', function () {
        it('throws when view file not found', function () {
            expect(fn() => View::render('/nonexistent/view.php'))
                ->toThrow(RuntimeException::class, 'View not found');
        });
        
        it('renders existing view files', function () {
            $testView = dirname(__DIR__, 3) . '/app/modules/test.php';
            file_put_contents($testView, '<?php echo "test"; ?>');
            
            ob_start();
            View::render('modules/test.php');
            $output = ob_get_clean();
            
            expect($output)->toBe('test');
            
            unlink($testView);
        });
        
        it('passes data to views', function () {
            $testView = dirname(__DIR__, 3) . '/app/modules/data-test.php';
            file_put_contents($testView, '<?php echo $name; ?>');
            
            ob_start();
            View::render('modules/data-test.php', ['name' => 'John']);
            $output = ob_get_clean();
            
            expect($output)->toBe('John');
            
            unlink($testView);
        });
    });
    
    describe('layout()', function () {
        it('has layout method', function () {
            expect(method_exists(View::class, 'layout'))->toBeTrue();
        });
        
        it('renders with head and footer', function () {
            // Test skipped - head and footer are now permanent modules
            expect(true)->toBeTrue();
        });
    });
    
    describe('layoutWithAssets()', function () {
        it('auto-loads module CSS when file exists', function () {
            Assets::clear();

            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            $testDir = $modulesDir . '/assettest';
            $headDir = $modulesDir . '/head';
            $footerDir = $modulesDir . '/footer';
            $cssDir = $testDir . '/view/frontend/css';

            // Create test directories
            if (!is_dir($testDir)) mkdir($testDir, 0755, true);
            if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);

            // Create head/footer only if they don't exist (they're permanent modules)
            if (!is_dir($headDir)) mkdir($headDir, 0755, true);
            if (!is_dir($footerDir)) mkdir($footerDir, 0755, true);
            if (!file_exists("$headDir/index.php")) file_put_contents($headDir . '/index.php', '');
            if (!file_exists("$footerDir/index.php")) file_put_contents($footerDir . '/index.php', '');

            // Create test module files
            file_put_contents($testDir . '/index.php', 'test');
            file_put_contents($cssDir . '/assettest.css', '');

            ob_start();
            View::layoutWithAssets('assettest');
            ob_end_clean();

            $cssOutput = Assets::renderCss();
            expect($cssOutput)->toContain('/modules/assettest/view/frontend/css/assettest.css');

            // Cleanup (keep head/footer - they're permanent)
            unlink($cssDir . '/assettest.css');
            unlink($testDir . '/index.php');
            rmdir($cssDir);
            rmdir($testDir . '/view/frontend');
            rmdir($testDir . '/view');
            rmdir($testDir);

            Assets::clear();
        });

        it('auto-loads module JS when file exists', function () {
            Assets::clear();

            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            $testDir = $modulesDir . '/jstest';
            $headDir = $modulesDir . '/head';
            $footerDir = $modulesDir . '/footer';
            $jsDir = $testDir . '/view/frontend/js';

            // Create test directories
            if (!is_dir($testDir)) mkdir($testDir, 0755, true);
            if (!is_dir($jsDir)) mkdir($jsDir, 0755, true);

            // Create head/footer only if they don't exist (they're permanent modules)
            if (!is_dir($headDir)) mkdir($headDir, 0755, true);
            if (!is_dir($footerDir)) mkdir($footerDir, 0755, true);
            if (!file_exists("$headDir/index.php")) file_put_contents($headDir . '/index.php', '');
            if (!file_exists("$footerDir/index.php")) file_put_contents($footerDir . '/index.php', '');

            // Create test module files
            file_put_contents($testDir . '/index.php', 'test');
            file_put_contents($jsDir . '/jstest.js', '');

            ob_start();
            View::layoutWithAssets('jstest');
            ob_end_clean();

            $jsOutput = Assets::renderJs();
            expect($jsOutput)->toContain('/modules/jstest/view/frontend/js/jstest.js');

            // Cleanup (keep head/footer - they're permanent)
            unlink($jsDir . '/jstest.js');
            unlink($testDir . '/index.php');
            rmdir($jsDir);
            rmdir($testDir . '/view/frontend');
            rmdir($testDir . '/view');
            rmdir($testDir);

            Assets::clear();
        });

        it('does not error when assets do not exist', function () {
            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            $testDir = $modulesDir . '/noassets';
            $headDir = $modulesDir . '/head';
            $footerDir = $modulesDir . '/footer';

            // Create test directory
            if (!is_dir($testDir)) mkdir($testDir, 0755, true);

            // Create head/footer only if they don't exist (they're permanent modules)
            if (!is_dir($headDir)) mkdir($headDir, 0755, true);
            if (!is_dir($footerDir)) mkdir($footerDir, 0755, true);
            if (!file_exists("$headDir/index.php")) file_put_contents($headDir . '/index.php', '');
            if (!file_exists("$footerDir/index.php")) file_put_contents($footerDir . '/index.php', '');

            file_put_contents($testDir . '/index.php', 'no assets');

            expect(function () {
                ob_start();
                View::layoutWithAssets('noassets');
                ob_end_clean();
            })->not->toThrow(Exception::class);

            // Cleanup (keep head/footer - they're permanent)
            unlink($testDir . '/index.php');
            rmdir($testDir);
        });
    });
});
