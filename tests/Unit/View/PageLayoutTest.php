<?php

declare(strict_types=1);

use App\Core\View\Layout\PageLayout;

test('frontend containers includes expected containers', function () {
    $containers = PageLayout::FRONTEND_CONTAINERS;
    
    expect($containers)->toContain('header');
    expect($containers)->toContain('content');
    expect($containers)->toContain('footer');
    expect($containers)->toContain('head.css');
    expect($containers)->toContain('body.js');
});

test('admin containers includes expected containers', function () {
    $containers = PageLayout::ADMIN_CONTAINERS;
    
    expect($containers)->toContain('sidebar');
    expect($containers)->toContain('header');
    expect($containers)->toContain('content');
    expect($containers)->toContain('page.title');
    expect($containers)->toContain('messages');
});

test('is valid container returns true for frontend container', function () {
    expect(PageLayout::isValidContainer('content', 'frontend'))->toBeTrue();
    expect(PageLayout::isValidContainer('header', 'frontend'))->toBeTrue();
    expect(PageLayout::isValidContainer('footer', 'frontend'))->toBeTrue();
});

test('is valid container returns false for invalid frontend container', function () {
    expect(PageLayout::isValidContainer('invalid.container', 'frontend'))->toBeFalse();
    expect(PageLayout::isValidContainer('sidebar', 'frontend'))->toBeFalse(); // sidebar is admin only
});

test('is valid container returns true for admin container', function () {
    expect(PageLayout::isValidContainer('sidebar', 'admin'))->toBeTrue();
    expect(PageLayout::isValidContainer('page.title', 'admin'))->toBeTrue();
    expect(PageLayout::isValidContainer('messages', 'admin'))->toBeTrue();
});

test('is valid container returns false for invalid admin container', function () {
    expect(PageLayout::isValidContainer('invalid.container', 'admin'))->toBeFalse();
    expect(PageLayout::isValidContainer('breadcrumbs', 'admin'))->toBeFalse(); // breadcrumbs is frontend only
});

test('is valid container defaults to frontend area', function () {
    expect(PageLayout::isValidContainer('content'))->toBeTrue();
    expect(PageLayout::isValidContainer('sidebar'))->toBeFalse();
});

test('get containers returns frontend containers by default', function () {
    $containers = PageLayout::getContainers();
    
    expect($containers)->toBe(PageLayout::FRONTEND_CONTAINERS);
});

test('get containers returns admin containers when specified', function () {
    $containers = PageLayout::getContainers('admin');
    
    expect($containers)->toBe(PageLayout::ADMIN_CONTAINERS);
});

test('get containers returns frontend containers when specified', function () {
    $containers = PageLayout::getContainers('frontend');
    
    expect($containers)->toBe(PageLayout::FRONTEND_CONTAINERS);
});
