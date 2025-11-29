<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Container\Container;
use App\Core\View\Layout\LayoutRenderer;

beforeEach(function () {
    Application::resetInstance();
    $this->app = new Application(BASE_PATH);
    $this->app->bootstrap();
    
    $this->container = new Container();
    $this->renderer = new LayoutRenderer($this->container);
});

afterEach(function () {
    Application::resetInstance();
});

// Area tests

test('set area returns self', function () {
    $result = $this->renderer->setArea('admin');
    
    expect($result)->toBe($this->renderer);
});

test('get area returns set area', function () {
    $this->renderer->setArea('admin');
    
    expect($this->renderer->getArea())->toBe('admin');
});

test('get area defaults to frontend', function () {
    expect($this->renderer->getArea())->toBe('frontend');
});

// Handle tests

test('add handle returns self', function () {
    $result = $this->renderer->addHandle('home_index');
    
    expect($result)->toBe($this->renderer);
});

test('add handle stores handle', function () {
    $this->renderer->addHandle('home_index');
    
    expect($this->renderer->getHandles())->toContain('home_index');
});

test('add handle accepts multiple handles', function () {
    $this->renderer->addHandle('default', 'home_index', 'custom');
    
    $handles = $this->renderer->getHandles();
    expect($handles)->toContain('default');
    expect($handles)->toContain('home_index');
    expect($handles)->toContain('custom');
});

test('add handle prevents duplicates', function () {
    $this->renderer->addHandle('home_index');
    $this->renderer->addHandle('home_index');
    
    $handles = $this->renderer->getHandles();
    expect(count(array_filter($handles, fn($h) => $h === 'home_index')))->toBe(1);
});

test('has handle returns true for existing handle', function () {
    $this->renderer->addHandle('home_index');
    
    expect($this->renderer->hasHandle('home_index'))->toBeTrue();
});

test('has handle returns false for missing handle', function () {
    expect($this->renderer->hasHandle('nonexistent'))->toBeFalse();
});

// Get block tests

test('get block returns null for missing block', function () {
    expect($this->renderer->getBlock('nonexistent'))->toBeNull();
});

// Has container tests

test('has container returns false when no blocks', function () {
    expect($this->renderer->hasContainer('content'))->toBeFalse();
});

// Render container tests

test('render container returns empty string when no blocks', function () {
    expect($this->renderer->renderContainer('content'))->toBe('');
});

// Load layout updates tests

test('load layout updates returns self', function () {
    $result = $this->renderer->loadLayoutUpdates();
    
    expect($result)->toBe($this->renderer);
});

test('load layout updates is idempotent', function () {
    $this->renderer->loadLayoutUpdates();
    $result = $this->renderer->loadLayoutUpdates();
    
    expect($result)->toBe($this->renderer);
});

// Generate blocks tests

test('generate blocks returns self', function () {
    $result = $this->renderer->generateBlocks();
    
    expect($result)->toBe($this->renderer);
});

test('generate blocks is idempotent', function () {
    $this->renderer->generateBlocks();
    $result = $this->renderer->generateBlocks();
    
    expect($result)->toBe($this->renderer);
});

// CSS/JS files tests

test('get css files returns empty array by default', function () {
    expect($this->renderer->getCssFiles())->toBe([]);
});

test('get js files returns empty array by default', function () {
    expect($this->renderer->getJsFiles())->toBe([]);
});

// Reset tests

test('reset returns self', function () {
    $result = $this->renderer->reset();
    
    expect($result)->toBe($this->renderer);
});

test('reset clears handles', function () {
    $this->renderer->addHandle('home_index');
    $this->renderer->reset();
    
    expect($this->renderer->getHandles())->toBe([]);
});

test('reset clears css files', function () {
    $this->renderer->reset();
    
    expect($this->renderer->getCssFiles())->toBe([]);
});

test('reset clears js files', function () {
    $this->renderer->reset();
    
    expect($this->renderer->getJsFiles())->toBe([]);
});

test('reset allows reloading layouts', function () {
    $this->renderer->loadLayoutUpdates();
    $this->renderer->reset();
    
    // Should be able to load again without being blocked by idempotent flag
    $result = $this->renderer->loadLayoutUpdates();
    expect($result)->toBe($this->renderer);
});
