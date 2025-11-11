<?php declare(strict_types=1);

use App\Base\Helpers\Assets;

describe('Assets Helper', function () {
    beforeEach(function () {
        Assets::clear();
    });

    afterEach(function () {
        Assets::clear();
    });

    describe('addCss()', function () {
        it('adds CSS file to module layer by default', function () {
            Assets::addCss('/modules/home/view/frontend/css/home.css');
            $output = Assets::renderCss();

            expect($output)->toContain('/modules/home/view/frontend/css/home.css');
        });

        it('adds CSS file to specific layer', function () {
            Assets::addCss('/app/base/view/base/css/base.css', 'base');
            $output = Assets::renderCss();

            expect($output)->toContain('/app/base/view/base/css/base.css');
        });

        it('prevents duplicate CSS files', function () {
            Assets::addCss('/test.css');
            Assets::addCss('/test.css');

            $output = Assets::renderCss();
            $count = substr_count($output, '/test.css');

            expect($count)->toBe(1);
        });

        it('throws on path with directory traversal', function () {
            expect(fn() => Assets::addCss('/../etc/passwd'))
                ->toThrow(\InvalidArgumentException::class, 'cannot contain ".."');
        });

        it('throws on path with null byte', function () {
            expect(fn() => Assets::addCss("/test\0.css"))
                ->toThrow(\InvalidArgumentException::class, 'cannot contain null bytes');
        });

        it('throws on relative path', function () {
            expect(fn() => Assets::addCss('test.css'))
                ->toThrow(\InvalidArgumentException::class, 'must start with "/"');
        });
    });

    describe('addJs()', function () {
        it('adds JS file to module layer by default', function () {
            Assets::addJs('/modules/home/view/frontend/js/home.js');
            $output = Assets::renderJs();

            expect($output)->toContain('/modules/home/view/frontend/js/home.js');
        });

        it('adds JS file to specific layer', function () {
            Assets::addJs('/app/base/view/base/js/base.js', 'base');
            $output = Assets::renderJs();

            expect($output)->toContain('/app/base/view/base/js/base.js');
        });

        it('prevents duplicate JS files', function () {
            Assets::addJs('/test.js');
            Assets::addJs('/test.js');

            $output = Assets::renderJs();
            $count = substr_count($output, '/test.js');

            expect($count)->toBe(1);
        });

        it('throws on invalid path', function () {
            expect(fn() => Assets::addJs('/../bad.js'))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('renderCss()', function () {
        it('renders CSS in correct order: base → frontend → module', function () {
            Assets::addCss('/base.css', 'base');
            Assets::addCss('/frontend.css', 'frontend');
            Assets::addCss('/module.css', 'module');

            $output = Assets::renderCss();

            $basePos = strpos($output, '/base.css');
            $frontendPos = strpos($output, '/frontend.css');
            $modulePos = strpos($output, '/module.css');

            expect($basePos)->toBeLessThan($frontendPos);
            expect($frontendPos)->toBeLessThan($modulePos);
        });

        it('includes cache busting version parameter', function () {
            Assets::addCss('/test.css');
            $output = Assets::renderCss();

            expect($output)->toMatch('/test.css\?v=\d+/');
        });

        it('uses custom version when set', function () {
            Assets::setVersion('1.2.3');
            Assets::addCss('/test.css');
            $output = Assets::renderCss();

            expect($output)->toContain('?v=1.2.3');
        });

        it('escapes HTML in output', function () {
            Assets::addCss('/test.css');
            $output = Assets::renderCss();

            expect($output)->toContain('<link rel="stylesheet"');
            expect($output)->not->toContain('<script');
        });
    });

    describe('renderJs()', function () {
        it('renders JS in correct order: base → frontend → module', function () {
            Assets::addJs('/base.js', 'base');
            Assets::addJs('/frontend.js', 'frontend');
            Assets::addJs('/module.js', 'module');

            $output = Assets::renderJs();

            $basePos = strpos($output, '/base.js');
            $frontendPos = strpos($output, '/frontend.js');
            $modulePos = strpos($output, '/module.js');

            expect($basePos)->toBeLessThan($frontendPos);
            expect($frontendPos)->toBeLessThan($modulePos);
        });

        it('includes cache busting version parameter', function () {
            Assets::addJs('/test.js');
            $output = Assets::renderJs();

            expect($output)->toMatch('/test.js\?v=\d+/');
        });
    });

    describe('setVersion()', function () {
        it('sets custom version for cache busting', function () {
            Assets::setVersion('2.0.0');
            Assets::addCss('/test.css');

            $output = Assets::renderCss();

            expect($output)->toContain('?v=2.0.0');
        });
    });

    describe('clear()', function () {
        it('clears all assets', function () {
            Assets::addCss('/test.css');
            Assets::addJs('/test.js');
            Assets::setVersion('1.0.0');

            Assets::clear();

            $cssOutput = Assets::renderCss();
            $jsOutput = Assets::renderJs();

            expect($cssOutput)->toBe('');
            expect($jsOutput)->toBe('');
        });
    });
});
