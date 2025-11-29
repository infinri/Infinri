<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\View\Asset\AssetManager;

beforeEach(function () {
    // Bootstrap app if not already done
    Application::resetInstance();
    $this->app = new Application(BASE_PATH);
    $this->app->bootstrap();
    
    $this->assets = new AssetManager('test-nonce-123');
});

afterEach(function () {
    Application::resetInstance();
});

test('it can be instantiated', function () {
    expect($this->assets)->toBeInstanceOf(AssetManager::class);
});

test('it stores and retrieves nonce', function () {
    expect($this->assets->getNonce())->toBe('test-nonce-123');
});

test('it can set nonce', function () {
    $this->assets->setNonce('new-nonce');
    expect($this->assets->getNonce())->toBe('new-nonce');
});

test('it can set area', function () {
    $this->assets->setArea('admin');
    expect($this->assets->getArea())->toBe('admin');
});

test('it defaults to frontend area', function () {
    expect($this->assets->getArea())->toBe('frontend');
});

test('it can add css files in development', function () {
    // In dev mode, files should be added
    $this->assets->addCss('/assets/test.css');
    $this->assets->addCss('/assets/theme.css');
    
    // Render will show them if in dev mode
    $html = $this->assets->renderCss();
    expect($html)->toContain('/assets/test.css');
});

test('it can add js files in development', function () {
    $this->assets->addJs('/assets/app.js');
    
    $html = $this->assets->renderJs();
    expect($html)->toContain('/assets/app.js');
});

test('it can add preconnect urls', function () {
    $this->assets->addPreconnect('https://fonts.googleapis.com');
    
    $html = $this->assets->renderPreconnects();
    expect($html)->toContain('https://fonts.googleapis.com');
    expect($html)->toContain('rel="preconnect"');
});

test('it can render inline styles with nonce', function () {
    $html = $this->assets->renderInlineStyle('body { color: red; }');
    
    expect($html)->toContain('<style');
    expect($html)->toContain('nonce="test-nonce-123"');
    expect($html)->toContain('body { color: red; }');
});

test('it can render inline scripts with nonce', function () {
    $html = $this->assets->renderInlineScript('console.log("test");');
    
    expect($html)->toContain('<script');
    expect($html)->toContain('nonce="test-nonce-123"');
    expect($html)->toContain('console.log("test");');
});

test('it can add head scripts with attributes', function () {
    $this->assets->addHeadScript('https://example.com/script.js', ['async' => true]);
    
    $html = $this->assets->renderHeadScripts();
    expect($html)->toContain('https://example.com/script.js');
});

test('it can set version for cache busting', function () {
    $this->assets->setVersion('1.0.0');
    $this->assets->addCss('/assets/test.css');
    
    $html = $this->assets->renderCss();
    expect($html)->toContain('v=1.0.0');
});

test('it returns self from setNonce', function () {
    $result = $this->assets->setNonce('nonce');
    expect($result)->toBe($this->assets);
});

test('it returns self from setArea', function () {
    $result = $this->assets->setArea('admin');
    expect($result)->toBe($this->assets);
});

test('it returns self from setVersion', function () {
    $result = $this->assets->setVersion('2.0.0');
    expect($result)->toBe($this->assets);
});

test('it returns self from addCss', function () {
    $result = $this->assets->addCss('/test.css');
    expect($result)->toBe($this->assets);
});

test('it returns self from addJs', function () {
    $result = $this->assets->addJs('/test.js');
    expect($result)->toBe($this->assets);
});

test('it returns self from addHeadScript', function () {
    $result = $this->assets->addHeadScript('https://example.com/script.js');
    expect($result)->toBe($this->assets);
});

test('it returns self from addPreconnect', function () {
    $result = $this->assets->addPreconnect('https://example.com');
    expect($result)->toBe($this->assets);
});

test('it returns self from reset', function () {
    $result = $this->assets->reset();
    expect($result)->toBe($this->assets);
});

test('reset clears all assets', function () {
    $this->assets->addCss('/test.css');
    $this->assets->addJs('/test.js');
    $this->assets->addHeadScript('https://example.com/script.js');
    $this->assets->addPreconnect('https://example.com');
    
    $this->assets->reset();
    
    expect($this->assets->renderCss())->toBe('');
    expect($this->assets->renderJs())->toBe('');
    expect($this->assets->renderHeadScripts())->toBe('');
    expect($this->assets->renderPreconnects())->toBe('');
});

test('it prevents duplicate css files', function () {
    $this->assets->addCss('/test.css');
    $this->assets->addCss('/test.css');
    
    $html = $this->assets->renderCss();
    expect(substr_count($html, '/test.css'))->toBe(1);
});

test('it prevents duplicate js files', function () {
    $this->assets->addJs('/test.js');
    $this->assets->addJs('/test.js');
    
    $html = $this->assets->renderJs();
    expect(substr_count($html, '/test.js'))->toBe(1);
});

test('it prevents duplicate preconnect urls', function () {
    $this->assets->addPreconnect('https://fonts.googleapis.com');
    $this->assets->addPreconnect('https://fonts.googleapis.com');
    
    $html = $this->assets->renderPreconnects();
    expect(substr_count($html, 'fonts.googleapis.com'))->toBe(1);
});

test('it resolves module notation for css', function () {
    $this->assets->addCss('TestModule::css/style.css');
    
    $html = $this->assets->renderCss();
    expect($html)->toContain('/assets/modules/TestModule/view/frontend/css/style.css');
});

test('it resolves module notation for js', function () {
    $this->assets->addJs('TestModule::js/app.js');
    
    $html = $this->assets->renderJs();
    expect($html)->toContain('/assets/modules/TestModule/view/frontend/js/app.js');
});

test('it resolves module notation with area', function () {
    $this->assets->setArea('admin');
    $this->assets->addCss('TestModule::css/admin.css');
    
    $html = $this->assets->renderCss();
    expect($html)->toContain('/assets/modules/TestModule/view/admin/css/admin.css');
});

test('it renders empty string for empty preconnects', function () {
    expect($this->assets->renderPreconnects())->toBe('');
});

test('it renders empty string for empty head scripts', function () {
    expect($this->assets->renderHeadScripts())->toBe('');
});

test('it renders empty string for empty inline style', function () {
    expect($this->assets->renderInlineStyle(''))->toBe('');
    expect($this->assets->renderInlineStyle('   '))->toBe('');
});

test('it renders empty string for empty inline script', function () {
    expect($this->assets->renderInlineScript(''))->toBe('');
    expect($this->assets->renderInlineScript('   '))->toBe('');
});

test('it renders inline style without nonce when null', function () {
    $assets = new AssetManager(null);
    $html = $assets->renderInlineStyle('body { color: red; }');
    
    expect($html)->toContain('<style>');
    expect($html)->not->toContain('nonce');
});

test('it renders inline script without nonce when null', function () {
    $assets = new AssetManager(null);
    $html = $assets->renderInlineScript('console.log("test");');
    
    expect($html)->toContain('<script>');
    expect($html)->not->toContain('nonce');
});

test('it renders head script with boolean true attribute', function () {
    $this->assets->addHeadScript('https://example.com/script.js', ['async' => true, 'defer' => true]);
    
    $html = $this->assets->renderHeadScripts();
    expect($html)->toContain(' async');
    expect($html)->toContain(' defer');
});

test('it renders head script with boolean false attribute', function () {
    $this->assets->addHeadScript('https://example.com/script.js', ['async' => false]);
    
    $html = $this->assets->renderHeadScripts();
    expect($html)->not->toContain('async');
});

test('it renders head script with null attribute', function () {
    $this->assets->addHeadScript('https://example.com/script.js', ['data-test' => null]);
    
    $html = $this->assets->renderHeadScripts();
    expect($html)->not->toContain('data-test');
});

test('it renders head script with string attribute', function () {
    $this->assets->addHeadScript('https://example.com/script.js', ['crossorigin' => 'anonymous']);
    
    $html = $this->assets->renderHeadScripts();
    expect($html)->toContain('crossorigin="anonymous"');
});

test('it checks production mode', function () {
    // In test environment, should not be production
    expect($this->assets->isProduction())->toBeFalse();
});

test('it renders critical CSS returns empty when file not found', function () {
    $html = $this->assets->renderCriticalCss();
    expect($html)->toBe('');
});

test('it uses time-based version when not set', function () {
    $this->assets->addCss('/test.css');
    
    $html = $this->assets->renderCss();
    expect($html)->toContain('?v=');
});
