<?php declare(strict_types=1);

use App\Helpers\Module;
use App\Helpers\Cache;

describe('Module Helper', function () {
    beforeEach(function () {
        // Create test module directories
        $modulesDir = dirname(__DIR__, 3) . '/app/modules';
        
        // Create test modules
        foreach (['testmod1', 'testmod2', 'invalid-Module'] as $mod) {
            $modDir = $modulesDir . '/' . $mod;
            if (!is_dir($modDir)) {
                mkdir($modDir, 0755, true);
            }
        }
    });
    
    afterEach(function () {
        // Clean up test modules
        $modulesDir = dirname(__DIR__, 3) . '/app/modules';
        foreach (['testmod1', 'testmod2', 'invalid-Module'] as $mod) {
            $modDir = $modulesDir . '/' . $mod;
            if (is_dir($modDir)) {
                rmdir($modDir);
            }
        }
    });
    
    describe('discover()', function () {
        it('returns array of valid modules', function () {
            // Clear cache first
            Cache::clear();
            
            // Create test module with proper file
            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            $testModuleDir = $modulesDir . '/testdiscover';
            if (!is_dir($testModuleDir)) {
                mkdir($testModuleDir, 0755, true);
            }
            file_put_contents($testModuleDir . '/TestdiscoverModule.php', '<?php');
            
            $modules = Module::discover();
            
            expect($modules)->toBeArray();
            expect($modules)->toContain('testdiscover');
            
            // Cleanup
            unlink($testModuleDir . '/TestdiscoverModule.php');
            rmdir($testModuleDir);
            Cache::clear();
        });
        
        it('filters module names', function () {
            Cache::clear();
            
            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            
            // Create a file (not directory) to test line 33
            file_put_contents($modulesDir . '/notadir.txt', 'test');
            
            $modules = Module::discover();
            
            // Should be array and should not include the file
            expect($modules)->toBeArray();
            expect($modules)->not->toContain('notadir.txt');
            
            // Cleanup
            unlink($modulesDir . '/notadir.txt');
            Cache::clear();
        });
        
        it('caches results', function () {
            $modules1 = Module::discover();
            $modules2 = Module::discover();
            
            // Second call should be from cache (same result)
            expect($modules1)->toBe($modules2);
        });
    });
    
    describe('getClassName()', function () {
        it('generates class name', function () {
            $className = Module::getClassName('testmod');
            
            expect($className)
                ->toBeString()
                ->toContain('App\\Modules\\')
                ->toContain('Testmod');
        });
        
        it('capitalizes module name', function () {
            $className = Module::getClassName('mymodule');
            
            expect($className)->toContain('Mymodule');
        });
    });
    
    describe('getClassFile()', function () {
        it('generates file path', function () {
            $filePath = Module::getClassFile('testmod');
            
            expect($filePath)
                ->toBeString()
                ->toContain('/modules/testmod/')
                ->toContain('Module.php');
        });
    });
    
    describe('hasAssets()', function () {
        it('returns false for modules without assets', function () {
            expect(Module::hasAssets('testmod1'))->toBeFalse();
        });
    });
});
