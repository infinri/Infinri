<?php declare(strict_types=1);

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

// Additional comprehensive tests for MetaManager

test('setTitle returns self', function () {
    $result = $this->meta->setTitle('Test');
    expect($result)->toBe($this->meta);
});

test('setDescription returns self', function () {
    $result = $this->meta->setDescription('Test');
    expect($result)->toBe($this->meta);
});

test('setKeywords returns self', function () {
    $result = $this->meta->setKeywords('test');
    expect($result)->toBe($this->meta);
});

test('setAuthor returns self', function () {
    $result = $this->meta->setAuthor('Author');
    expect($result)->toBe($this->meta);
});

test('setRobots returns self', function () {
    $result = $this->meta->setRobots('index');
    expect($result)->toBe($this->meta);
});

test('noIndex returns self', function () {
    $result = $this->meta->noIndex();
    expect($result)->toBe($this->meta);
});

test('setCanonical returns self', function () {
    $result = $this->meta->setCanonical('/page');
    expect($result)->toBe($this->meta);
});

test('set returns self', function () {
    $result = $this->meta->set('custom', 'value');
    expect($result)->toBe($this->meta);
});

test('setOpenGraph returns self', function () {
    $result = $this->meta->setOpenGraph('title', 'test');
    expect($result)->toBe($this->meta);
});

test('setOpenGraphTags returns self', function () {
    $result = $this->meta->setOpenGraphTags(['title' => 'test']);
    expect($result)->toBe($this->meta);
});

test('setTwitterCard returns self', function () {
    $result = $this->meta->setTwitterCard('card', 'summary');
    expect($result)->toBe($this->meta);
});

test('setTwitterTags returns self', function () {
    $result = $this->meta->setTwitterTags(['card' => 'summary']);
    expect($result)->toBe($this->meta);
});

test('setFavicon returns self', function () {
    $result = $this->meta->setFavicon('/favicon.png');
    expect($result)->toBe($this->meta);
});

test('addLink returns self', function () {
    $result = $this->meta->addLink('canonical', '/page');
    expect($result)->toBe($this->meta);
});

test('sync returns self', function () {
    $result = $this->meta->sync();
    expect($result)->toBe($this->meta);
});

test('reset returns self', function () {
    $result = $this->meta->reset();
    expect($result)->toBe($this->meta);
});

test('setTitleSuffix returns self', function () {
    $result = $this->meta->setTitleSuffix(' | Site');
    expect($result)->toBe($this->meta);
});

test('title suffix is appended by default', function () {
    $this->meta->setTitleSuffix(' | My Site');
    $this->meta->setTitle('Page');
    
    expect($this->meta->getTitle())->toBe('Page | My Site');
});

test('title suffix can be disabled', function () {
    $this->meta->setTitleSuffix(' | My Site');
    $this->meta->setTitle('Page', false);
    
    expect($this->meta->getTitle())->toBe('Page');
});

test('get returns null for missing tag', function () {
    expect($this->meta->get('nonexistent'))->toBeNull();
});

test('noIndex with nofollow', function () {
    $this->meta->noIndex(true);
    
    $html = $this->meta->render();
    expect($html)->toContain('noindex, nofollow');
});

test('noIndex without nofollow', function () {
    $this->meta->noIndex(false);
    
    $html = $this->meta->render();
    expect($html)->toContain('noindex, follow');
});

test('setOpenGraph adds og prefix if missing', function () {
    $this->meta->setOpenGraph('title', 'Test');
    
    $html = $this->meta->render();
    expect($html)->toContain('og:title');
});

test('setOpenGraph preserves og prefix', function () {
    $this->meta->setOpenGraph('og:title', 'Test');
    
    $html = $this->meta->render();
    expect($html)->toContain('og:title');
});

test('setTwitterCard adds twitter prefix if missing', function () {
    $this->meta->setTwitterCard('card', 'summary');
    
    $html = $this->meta->render();
    expect($html)->toContain('twitter:card');
});

test('setTwitterCard preserves twitter prefix', function () {
    $this->meta->setTwitterCard('twitter:card', 'summary');
    
    $html = $this->meta->render();
    expect($html)->toContain('twitter:card');
});

test('setOpenGraphTags sets multiple tags', function () {
    $this->meta->setOpenGraphTags([
        'title' => 'OG Title',
        'description' => 'OG Desc',
        'type' => 'website',
    ]);
    
    $html = $this->meta->render();
    expect($html)->toContain('og:title');
    expect($html)->toContain('og:description');
    expect($html)->toContain('og:type');
});

test('setTwitterTags sets multiple tags', function () {
    $this->meta->setTwitterTags([
        'card' => 'summary_large_image',
        'title' => 'Twitter Title',
    ]);
    
    $html = $this->meta->render();
    expect($html)->toContain('twitter:card');
    expect($html)->toContain('twitter:title');
});

test('setFavicon sets icon and apple-touch-icon', function () {
    $this->meta->setFavicon('/favicon.png');
    
    $html = $this->meta->render();
    expect($html)->toContain('rel="icon"');
    expect($html)->toContain('rel="apple-touch-icon"');
});

test('setFavicon accepts custom type', function () {
    $this->meta->setFavicon('/favicon.ico', 'image/x-icon');
    
    $html = $this->meta->render();
    expect($html)->toContain('type="image/x-icon"');
});

test('render includes charset', function () {
    $html = $this->meta->render();
    expect($html)->toContain('charset="UTF-8"');
});

test('render includes viewport', function () {
    $html = $this->meta->render();
    expect($html)->toContain('name="viewport"');
    expect($html)->toContain('width=device-width');
});

test('render skips empty meta values', function () {
    $this->meta->set('empty', '');
    
    $html = $this->meta->render();
    expect($html)->not->toContain('name="empty"');
});

test('render skips empty og values', function () {
    $this->meta->setOpenGraph('empty', '');
    
    $html = $this->meta->render();
    expect($html)->not->toContain('og:empty');
});

test('render skips empty twitter values', function () {
    $this->meta->setTwitterCard('empty', '');
    
    $html = $this->meta->render();
    expect($html)->not->toContain('twitter:empty');
});

test('reset clears all tags', function () {
    $this->meta->setTitle('Test');
    $this->meta->setDescription('Desc');
    $this->meta->setOpenGraph('title', 'OG');
    $this->meta->setTwitterCard('card', 'summary');
    $this->meta->setCanonical('/page');
    $this->meta->setRobots('noindex');
    
    $this->meta->reset();
    
    expect($this->meta->get('description'))->toBeNull();
});

test('sync does not overwrite existing og tags', function () {
    $this->meta->setOpenGraph('title', 'Existing OG Title');
    $this->meta->setTitle('Page Title', false);
    $this->meta->sync();
    
    $html = $this->meta->render();
    expect($html)->toContain('Existing OG Title');
});

test('sync does not overwrite existing twitter tags', function () {
    $this->meta->setTwitterCard('title', 'Existing Twitter Title');
    $this->meta->setTitle('Page Title', false);
    $this->meta->sync();
    
    $html = $this->meta->render();
    expect($html)->toContain('Existing Twitter Title');
});

test('addLink with extra attributes', function () {
    $this->meta->addLink('preload', '/font.woff2', ['as' => 'font', 'crossorigin' => 'anonymous']);
    
    $html = $this->meta->render();
    expect($html)->toContain('rel="preload"');
    expect($html)->toContain('as="font"');
    expect($html)->toContain('crossorigin="anonymous"');
});
