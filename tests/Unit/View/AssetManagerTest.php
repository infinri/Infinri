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
