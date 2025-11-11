<?php declare(strict_types=1);

use App\Base\Helpers\Meta;

describe('Meta Helper', function () {
    beforeEach(function () {
        Meta::clear();
    });

    afterEach(function () {
        Meta::clear();
    });

    describe('set()', function () {
        it('sets a single meta tag', function () {
            Meta::set('title', 'Test Page');

            expect(Meta::get('title'))->toBe('Test Page');
        });

        it('sets description', function () {
            Meta::set('description', 'Test description');

            expect(Meta::get('description'))->toBe('Test description');
        });
    });

    describe('setMultiple()', function () {
        it('sets multiple meta tags at once', function () {
            Meta::setMultiple([
                'title' => 'Multi Test',
                'description' => 'Multi description',
                'keywords' => 'test, meta'
            ]);

            expect(Meta::get('title'))->toBe('Multi Test');
            expect(Meta::get('description'))->toBe('Multi description');
            expect(Meta::get('keywords'))->toBe('test, meta');
        });
    });

    describe('get()', function () {
        it('returns meta tag value', function () {
            Meta::set('author', 'John Doe');

            expect(Meta::get('author'))->toBe('John Doe');
        });

        it('returns empty string for missing key', function () {
            expect(Meta::get('nonexistent'))->toBe('');
        });

        it('returns default title', function () {
            expect(Meta::get('title'))->toBe('Portfolio');
        });
    });

    describe('render()', function () {
        it('renders charset meta tag', function () {
            $output = Meta::render();

            expect($output)->toContain('<meta charset="UTF-8">');
        });

        it('renders viewport meta tag', function () {
            $output = Meta::render();

            expect($output)->toContain('<meta name="viewport"');
            expect($output)->toContain('width=device-width');
        });

        it('renders title tag', function () {
            Meta::set('title', 'Test Title');
            $output = Meta::render();

            expect($output)->toContain('<title>Test Title</title>');
        });

        it('renders description when set', function () {
            Meta::set('description', 'Test description');
            $output = Meta::render();

            expect($output)->toContain('<meta name="description"');
            expect($output)->toContain('Test description');
        });

        it('does not render empty description', function () {
            Meta::set('description', '');
            $output = Meta::render();

            expect($output)->not->toContain('<meta name="description"');
        });

        it('renders keywords when set', function () {
            Meta::set('keywords', 'php, test');
            $output = Meta::render();

            expect($output)->toContain('<meta name="keywords"');
            expect($output)->toContain('php, test');
        });

        it('renders Open Graph tags', function () {
            Meta::setMultiple([
                'og:title' => 'OG Title',
                'og:description' => 'OG Description',
                'og:image' => '/images/og.jpg'
            ]);

            $output = Meta::render();

            expect($output)->toContain('<meta property="og:title"');
            expect($output)->toContain('OG Title');
            expect($output)->toContain('OG Description');
        });

        it('does not render empty Open Graph tags', function () {
            Meta::set('og:title', '');
            $output = Meta::render();

            // Should not have og:title with empty content
            $lines = explode("\n", $output);
            $ogTitleLines = array_filter($lines, fn($line) => str_contains($line, 'og:title') && str_contains($line, 'content=""'));

            expect(count($ogTitleLines))->toBe(0);
        });

        it('renders Twitter card meta tags', function () {
            Meta::setMultiple([
                'twitter:card' => 'summary',
                'twitter:title' => 'Twitter Title',
                'twitter:description' => 'Twitter Desc'
            ]);

            $output = Meta::render();

            expect($output)->toContain('<meta name="twitter:card"');
            expect($output)->toContain('summary');
            expect($output)->toContain('Twitter Title');
        });

        it('escapes HTML in meta values', function () {
            Meta::set('title', '<script>alert("xss")</script>');
            $output = Meta::render();

            expect($output)->toContain('&lt;script&gt;');
            expect($output)->not->toContain('<script>alert');
        });
    });

    describe('clear()', function () {
        it('resets all meta tags to defaults', function () {
            Meta::setMultiple([
                'title' => 'Custom Title',
                'description' => 'Custom description',
                'keywords' => 'custom, keywords'
            ]);

            Meta::clear();

            expect(Meta::get('title'))->toBe('Portfolio');
            expect(Meta::get('description'))->toBe('');
            expect(Meta::get('keywords'))->toBe('');
        });
    });
});
