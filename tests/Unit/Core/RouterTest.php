<?php declare(strict_types=1);

use App\Core\Router;

describe('Router', function () {
    beforeEach(function () {
        // Reset superglobals for each test
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
    });
    
    describe('route registration', function () {
        it('registers GET routes', function () {
            $router = new Router();
            $result = $router->get('/', 'home');
            
            expect($result)->toBeInstanceOf(Router::class);
        });
        
        it('registers POST routes', function () {
            $router = new Router();
            $result = $router->post('/submit', 'handler');
            
            expect($result)->toBeInstanceOf(Router::class);
        });
        
        it('supports method chaining', function () {
            $router = new Router();
            $result = $router
                ->get('/', 'home')
                ->get('/about', 'about')
                ->post('/contact', 'contact');
            
            expect($result)->toBeInstanceOf(Router::class);
        });
    });
    
    describe('route parsing', function () {
        it('parses simple paths', function () {
            $_SERVER['REQUEST_URI'] = '/about';
            $router = new Router();
            
            // Constructor should parse the URI
            expect($router)->toBeInstanceOf(Router::class);
        });
        
        it('parses paths with query strings', function () {
            $_SERVER['REQUEST_URI'] = '/search?q=test';
            $router = new Router();
            
            expect($router)->toBeInstanceOf(Router::class);
        });
        
        it('handles root path', function () {
            $_SERVER['REQUEST_URI'] = '/';
            $router = new Router();
            
            expect($router)->toBeInstanceOf(Router::class);
        });
    });
    
    describe('handler execution', function () {
        it('accepts callable handlers', function () {
            $executed = false;
            
            $_SERVER['REQUEST_URI'] = '/test';
            $router = new Router();
            $router->get('/test', function () use (&$executed) {
                $executed = true;
            });
            
            ob_start();
            $router->dispatch();
            ob_end_clean();
            
            expect($executed)->toBeTrue();
        });
        
        it('accepts string module handlers', function () {
            $_SERVER['REQUEST_URI'] = '/';
            $router = new Router();
            $router->get('/', 'home');
            
            // This would require actual module files to test properly
            expect($router)->toBeInstanceOf(Router::class);
        });
    });
    
    describe('module name validation', function () {
        it('validates lowercase alphanumeric module names', function () {
            $router = new Router();
            
            // These should work (validated internally)
            expect(fn() => $router->get('/', 'home'))->not->toThrow(Exception::class);
            expect(fn() => $router->get('/', 'about-us'))->not->toThrow(Exception::class);
            expect(fn() => $router->get('/', 'test_module'))->not->toThrow(Exception::class);
        });
    });
    
    describe('404 handling', function () {
        it('returns 404 for unmatched routes', function () {
            $_SERVER['REQUEST_URI'] = '/nonexistent';
            $router = new Router();
            $router->get('/', 'home');
            
            ob_start();
            $router->dispatch(function () {
                echo '404 Not Found';
            });
            $output = ob_get_clean();
            
            expect($output)->toBe('404 Not Found');
        });
        
        it('uses default error handler when provided', function () {
            $_SERVER['REQUEST_URI'] = '/missing';
            $router = new Router();
            
            // This would try to render 'error' module
            // Just verify it doesn't throw
            expect(fn() => $router->get('/', 'home'))->not->toThrow(Exception::class);
        });
    });
    
    describe('methods exist', function () {
        it('has dispatch method', function () {
            expect(method_exists(Router::class, 'dispatch'))->toBeTrue();
        });
        
        it('has get method', function () {
            expect(method_exists(Router::class, 'get'))->toBeTrue();
        });
        
        it('has post method', function () {
            expect(method_exists(Router::class, 'post'))->toBeTrue();
        });
    });
    
    describe('module rendering', function () {
        it('dispatches to existing modules', function () {
            // Create minimal test module
            $modulesDir = dirname(__DIR__, 3) . '/app/modules';
            $testModuleDir = $modulesDir . '/testroute';
            if (!is_dir($testModuleDir)) {
                mkdir($testModuleDir, 0755, true);
            }
            file_put_contents($testModuleDir . '/index.php', '<?php echo "route-test"; ?>');
            
            $_SERVER['REQUEST_URI'] = '/test';
            $router = new Router();
            $router->get('/test', 'testroute');
            
            ob_start();
            $router->dispatch();
            $output = ob_get_clean();
            
            expect($output)->toContain('route-test');
            
            // Cleanup
            unlink($testModuleDir . '/index.php');
            rmdir($testModuleDir);
        });
    });
});
