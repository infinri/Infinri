<?php declare(strict_types=1);

use App\Core\View\Template\TemplateResolver;
use App\Core\Contracts\View\TemplateResolverInterface;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/template_resolver_test_' . uniqid();
    mkdir($this->tempDir, 0755, true);
    
    // Create directory structure
    mkdir($this->tempDir . '/Modules/Theme/view/frontend/templates/contact', 0755, true);
    mkdir($this->tempDir . '/Modules/Contact/view/frontend/templates', 0755, true);
    mkdir($this->tempDir . '/Core/View/view/frontend/templates', 0755, true);
    
    $this->resolver = new TemplateResolver($this->tempDir);
});

afterEach(function () {
    // Cleanup
    if (is_dir($this->tempDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
        @rmdir($this->tempDir);
    }
});

test('implements TemplateResolverInterface', function () {
    expect($this->resolver)->toBeInstanceOf(TemplateResolverInterface::class);
});

test('get theme returns default theme', function () {
    expect($this->resolver->getTheme())->toBe('Theme');
});

test('set theme changes active theme', function () {
    $this->resolver->setTheme('CustomTheme');
    expect($this->resolver->getTheme())->toBe('CustomTheme');
});

test('set theme to null disables theme', function () {
    $this->resolver->setTheme(null);
    expect($this->resolver->getTheme())->toBeNull();
});

test('resolve returns null when template not found', function () {
    $result = $this->resolver->resolve('missing.phtml', 'Contact');
    expect($result)->toBeNull();
});

test('exists returns false when template not found', function () {
    expect($this->resolver->exists('missing.phtml', 'Contact'))->toBeFalse();
});

test('resolve finds theme override first', function () {
    // Create both theme override and module template
    file_put_contents($this->tempDir . '/Modules/Theme/view/frontend/templates/contact/form.phtml', 'theme');
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'module');
    
    $result = $this->resolver->resolve('form.phtml', 'Contact');
    
    expect($result)->toContain('Modules/Theme/view/frontend/templates/contact/form.phtml');
});

test('resolve finds module template when no theme override', function () {
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'module');
    
    $result = $this->resolver->resolve('form.phtml', 'Contact');
    
    expect($result)->toContain('Modules/Contact/view/frontend/templates/form.phtml');
});

test('resolve finds core fallback when no module template', function () {
    file_put_contents($this->tempDir . '/Core/View/view/frontend/templates/default.phtml', 'core');
    
    $result = $this->resolver->resolve('default.phtml', 'Unknown');
    
    expect($result)->toContain('Core/View/view/frontend/templates/default.phtml');
});

test('resolve skips theme when theme is null', function () {
    file_put_contents($this->tempDir . '/Modules/Theme/view/frontend/templates/contact/form.phtml', 'theme');
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'module');
    
    $this->resolver->setTheme(null);
    $result = $this->resolver->resolve('form.phtml', 'Contact');
    
    expect($result)->toContain('Modules/Contact/view/frontend/templates/form.phtml');
});

test('exists returns true when template found', function () {
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'content');
    
    expect($this->resolver->exists('form.phtml', 'Contact'))->toBeTrue();
});

test('get resolution paths returns correct order', function () {
    $paths = $this->resolver->getResolutionPaths('form.phtml', 'Contact', 'frontend');
    
    expect($paths)->toBeArray();
    expect($paths[0])->toContain('Theme/view/frontend/templates/contact/form.phtml');
    expect($paths[1])->toContain('Modules/Contact/view/frontend/templates/form.phtml');
    expect($paths[2])->toContain('Core/View/view/frontend/templates/form.phtml');
});

test('get resolution paths excludes theme when null', function () {
    $this->resolver->setTheme(null);
    $paths = $this->resolver->getResolutionPaths('form.phtml', 'Contact', 'frontend');
    
    expect($paths[0])->toContain('Modules/Contact/view/frontend/templates/form.phtml');
});

test('resolve caches results', function () {
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'content');
    
    $result1 = $this->resolver->resolve('form.phtml', 'Contact');
    $result2 = $this->resolver->resolve('form.phtml', 'Contact');
    
    expect($result1)->toBe($result2);
});

test('clear cache removes cached results', function () {
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'content');
    
    $this->resolver->resolve('form.phtml', 'Contact');
    $this->resolver->clearCache();
    
    // Still works after cache clear
    expect($this->resolver->exists('form.phtml', 'Contact'))->toBeTrue();
});

test('set theme clears cache', function () {
    file_put_contents($this->tempDir . '/Modules/Theme/view/frontend/templates/contact/form.phtml', 'theme');
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/templates/form.phtml', 'module');
    
    $result1 = $this->resolver->resolve('form.phtml', 'Contact');
    expect($result1)->toContain('Theme');
    
    $this->resolver->setTheme(null);
    $result2 = $this->resolver->resolve('form.phtml', 'Contact');
    expect($result2)->not->toContain('Theme');
});

test('resolve layout returns null when not found', function () {
    $result = $this->resolver->resolveLayout('missing.xml');
    expect($result)->toBeNull();
});

test('resolve layout finds theme layout first', function () {
    @mkdir($this->tempDir . '/Modules/Theme/view/frontend/layout', 0755, true);
    @mkdir($this->tempDir . '/Core/View/view/frontend/layout', 0755, true);
    file_put_contents($this->tempDir . '/Modules/Theme/view/frontend/layout/default.xml', 'theme');
    file_put_contents($this->tempDir . '/Core/View/view/frontend/layout/default.xml', 'core');
    
    $result = $this->resolver->resolveLayout('default.xml');
    expect($result)->toContain('Modules/Theme/view/frontend/layout/default.xml');
});

test('resolve layout finds core layout', function () {
    @mkdir($this->tempDir . '/Core/View/view/frontend/layout', 0755, true);
    file_put_contents($this->tempDir . '/Core/View/view/frontend/layout/default.xml', 'core');
    
    $result = $this->resolver->resolveLayout('default.xml');
    expect($result)->toContain('Core/View/view/frontend/layout/default.xml');
});

test('resolve asset returns null when not found', function () {
    $result = $this->resolver->resolveAsset('missing.css', 'Contact');
    expect($result)->toBeNull();
});

test('resolve asset finds theme override first', function () {
    @mkdir($this->tempDir . '/Modules/Theme/view/frontend/web/contact', 0755, true);
    @mkdir($this->tempDir . '/Modules/Contact/view/frontend/web', 0755, true);
    file_put_contents($this->tempDir . '/Modules/Theme/view/frontend/web/contact/styles.css', 'theme');
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/web/styles.css', 'module');
    
    $result = $this->resolver->resolveAsset('styles.css', 'Contact');
    expect($result)->toContain('Modules/Theme/view/frontend/web/contact/styles.css');
});

test('resolve asset finds module asset', function () {
    @mkdir($this->tempDir . '/Modules/Contact/view/frontend/web', 0755, true);
    file_put_contents($this->tempDir . '/Modules/Contact/view/frontend/web/styles.css', 'module');
    
    $result = $this->resolver->resolveAsset('styles.css', 'Contact');
    expect($result)->toContain('Modules/Contact/view/frontend/web/styles.css');
});

test('works with admin area', function () {
    @mkdir($this->tempDir . '/Modules/Contact/view/admin/templates', 0755, true);
    file_put_contents($this->tempDir . '/Modules/Contact/view/admin/templates/list.phtml', 'admin');
    
    $result = $this->resolver->resolve('list.phtml', 'Contact', 'admin');
    expect($result)->toContain('view/admin/templates/list.phtml');
});
