<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\View\Meta\MetaManager;

beforeEach(function () {
    // Bootstrap app for config() and e() helpers
    Application::resetInstance();
    $this->app = new Application(BASE_PATH);
    $this->app->bootstrap();
    
    $this->meta = new MetaManager();
});

afterEach(function () {
    Application::resetInstance();
});

test('it can be instantiated', function () {
    expect($this->meta)->toBeInstanceOf(MetaManager::class);
});

test('it can set and get title', function () {
    $this->meta->setTitle('Test Page', false); // false = no suffix
    expect($this->meta->getTitle())->toBe('Test Page');
});

test('it can set description', function () {
    $this->meta->setDescription('This is a test description');
    expect($this->meta->get('description'))->toBe('This is a test description');
});

test('it can set canonical url', function () {
    $this->meta->setCanonical('https://example.com/page');
    // Canonical is rendered in output
    $html = $this->meta->render();
    expect($html)->toContain('https://example.com/page');
});

test('it can set robots', function () {
    $this->meta->setRobots('noindex, nofollow');
    $html = $this->meta->render();
    expect($html)->toContain('noindex, nofollow');
});

test('it can set open graph properties', function () {
    $this->meta->setOpenGraph('title', 'OG Title');
    $this->meta->setOpenGraph('description', 'OG Description');
    
    $html = $this->meta->render();
    expect($html)->toContain('og:title');
    expect($html)->toContain('OG Title');
});

test('it can set twitter card properties', function () {
    $this->meta->setTwitterCard('card', 'summary');
    $this->meta->setTwitterCard('title', 'Twitter Title');
    
    $html = $this->meta->render();
    expect($html)->toContain('twitter:card');
    expect($html)->toContain('summary');
});

test('it can render all meta tags', function () {
    $this->meta->setTitle('Test', false);
    $this->meta->setDescription('Description');
    
    $html = $this->meta->render();
    
    expect($html)->toContain('<title>');
    expect($html)->toContain('Test');
    expect($html)->toContain('name="description"');
});

test('it can set keywords as string', function () {
    $this->meta->setKeywords('test, keywords, seo');
    expect($this->meta->get('keywords'))->toBe('test, keywords, seo');
});

test('it can set keywords as array', function () {
    $this->meta->setKeywords(['test', 'keywords', 'seo']);
    expect($this->meta->get('keywords'))->toBe('test, keywords, seo');
});

test('it can set author', function () {
    $this->meta->setAuthor('John Doe');
    expect($this->meta->get('author'))->toBe('John Doe');
});

test('it can noindex a page', function () {
    $this->meta->noIndex();
    $html = $this->meta->render();
    expect($html)->toContain('noindex');
});

test('it can add custom link tags', function () {
    $this->meta->addLink('alternate', '/feed.xml', ['type' => 'application/rss+xml']);
    $html = $this->meta->render();
    expect($html)->toContain('rel="alternate"');
    expect($html)->toContain('/feed.xml');
});

test('it syncs og tags from meta', function () {
    $this->meta->setTitle('Page Title', false);
    $this->meta->setDescription('Page description');
    $this->meta->sync();
    
    $html = $this->meta->render();
    expect($html)->toContain('og:title');
    expect($html)->toContain('Page Title');
});
