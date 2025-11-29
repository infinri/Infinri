<?php

declare(strict_types=1);

use App\Core\Module\AbstractModule;
use App\Core\Contracts\Module\ModuleInterface;
use App\Core\Contracts\Container\ContainerInterface;

// Test module implementation
class TestableModule extends AbstractModule
{
    protected string $name = 'testable';
    protected string $version = '2.0.0';
    protected string $description = 'A test module';
    protected array $dependencies = ['core', 'database'];
    protected array $providers = [];
    protected array $commands = [];
}

class AutoNameModule extends AbstractModule
{
    // Name will be auto-detected
}

test('abstract module implements ModuleInterface', function () {
    $module = new TestableModule();
    expect($module)->toBeInstanceOf(ModuleInterface::class);
});

test('get name returns configured name', function () {
    $module = new TestableModule();
    expect($module->getName())->toBe('testable');
});

test('get name auto detects from class name', function () {
    $module = new AutoNameModule();
    expect($module->getName())->toBe('autoname');
});

test('get version returns configured version', function () {
    $module = new TestableModule();
    expect($module->getVersion())->toBe('2.0.0');
});

test('get dependencies returns configured dependencies', function () {
    $module = new TestableModule();
    expect($module->getDependencies())->toBe(['core', 'database']);
});

test('get providers returns empty array by default', function () {
    $module = new TestableModule();
    expect($module->getProviders())->toBe([]);
});

test('get commands returns empty array by default', function () {
    $module = new TestableModule();
    expect($module->getCommands())->toBe([]);
});

test('is enabled returns true by default', function () {
    $module = new TestableModule();
    expect($module->isEnabled())->toBeTrue();
});

test('get description returns configured description', function () {
    $module = new TestableModule();
    expect($module->getDescription())->toBe('A test module');
});

test('get path returns module directory', function () {
    $module = new TestableModule();
    $path = $module->getPath();
    
    expect($path)->toBeString();
    expect($path)->toContain('tests');
});

test('get file path returns full path', function () {
    $module = new TestableModule();
    $path = $module->getFilePath('view/frontend/templates/test.phtml');
    
    expect($path)->toContain('view/frontend/templates/test.phtml');
});

test('has file returns false for non existent file', function () {
    $module = new TestableModule();
    expect($module->hasFile('non-existent.php'))->toBeFalse();
});

test('get view path returns view directory', function () {
    $module = new TestableModule();
    $path = $module->getViewPath('frontend');
    
    expect($path)->toContain('view/frontend');
});

test('has views returns false for test module', function () {
    $module = new TestableModule();
    expect($module->hasViews('frontend'))->toBeFalse();
});

test('register can be called with container', function () {
    $module = new TestableModule();
    $container = Mockery::mock(ContainerInterface::class);
    
    // Should not throw
    $module->register($container);
    expect(true)->toBeTrue();
});

test('boot can be called with container', function () {
    $module = new TestableModule();
    $container = Mockery::mock(ContainerInterface::class);
    
    // Should not throw
    $module->boot($container);
    expect(true)->toBeTrue();
});
