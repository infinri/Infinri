<?php declare(strict_types=1);

use App\Core\Application;
use App\Core\Console\Commands\AssetsPublishCommand;
use App\Core\Console\Commands\AssetsBuildCommand;

beforeEach(function () {
    Application::resetInstance();
    $this->app = new Application(BASE_PATH);
    $this->app->bootstrap();
});

afterEach(function () {
    Application::resetInstance();
});

// AssetsPublishCommand tests

test('assets publish command can be instantiated', function () {
    $command = new AssetsPublishCommand();
    expect($command)->toBeInstanceOf(AssetsPublishCommand::class);
});

test('assets publish command has correct name', function () {
    $command = new AssetsPublishCommand();
    expect($command->getName())->toBe('assets:publish');
});

test('assets publish command has description', function () {
    $command = new AssetsPublishCommand();
    expect($command->getDescription())->not->toBeEmpty();
});

test('assets publish command has aliases', function () {
    $command = new AssetsPublishCommand();
    $aliases = $command->getAliases();
    expect($aliases)->toContain('a:pub');
});

test('assets publish command handle returns zero', function () {
    $command = new AssetsPublishCommand();
    
    ob_start();
    $result = $command->handle(['assets:publish']);
    ob_end_clean();
    
    expect($result)->toBe(0);
});

test('assets publish command clears assets when called with clear', function () {
    $command = new AssetsPublishCommand();
    
    ob_start();
    $result = $command->handle(['assets:clear']);
    ob_end_clean();
    
    expect($result)->toBe(0);
});

test('assets publish command defaults to publish action', function () {
    $command = new AssetsPublishCommand();
    
    ob_start();
    $result = $command->handle([]);
    ob_end_clean();
    
    expect($result)->toBe(0);
});

test('assets publish command handles unknown command as publish', function () {
    $command = new AssetsPublishCommand();
    
    ob_start();
    $result = $command->handle(['unknown:command']);
    ob_end_clean();
    
    expect($result)->toBe(0);
});

// AssetsBuildCommand tests

test('assets build command can be instantiated', function () {
    $command = new AssetsBuildCommand();
    expect($command)->toBeInstanceOf(AssetsBuildCommand::class);
});

test('assets build command has correct name', function () {
    $command = new AssetsBuildCommand();
    expect($command->getName())->toBe('assets:build');
});

test('assets build command has description', function () {
    $command = new AssetsBuildCommand();
    expect($command->getDescription())->not->toBeEmpty();
});

test('assets build command has aliases', function () {
    $command = new AssetsBuildCommand();
    $aliases = $command->getAliases();
    expect($aliases)->toContain('a:build');
});

test('assets build command blocks in production', function () {
    // Store original
    $originalEnv = $_ENV['APP_ENV'] ?? null;
    
    // Set production
    $_ENV['APP_ENV'] = 'production';
    putenv('APP_ENV=production');
    
    $command = new AssetsBuildCommand();
    
    ob_start();
    $result = $command->handle([]);
    ob_end_clean();
    
    // Should fail in production
    expect($result)->toBe(1);
    
    // Restore
    if ($originalEnv !== null) {
        $_ENV['APP_ENV'] = $originalEnv;
        putenv("APP_ENV={$originalEnv}");
    } else {
        unset($_ENV['APP_ENV']);
        putenv('APP_ENV');
    }
});

test('assets build command runs in development', function () {
    // Store original
    $originalEnv = $_ENV['APP_ENV'] ?? null;
    
    // Set development
    $_ENV['APP_ENV'] = 'development';
    putenv('APP_ENV=development');
    
    $command = new AssetsBuildCommand();
    
    ob_start();
    $result = $command->handle([]);
    $output = ob_get_clean();
    
    // Command runs (may fail due to node/npm but won't be blocked)
    // If node exists and npm install works, it should complete
    // Check that it at least started (not blocked by production check)
    expect($output)->toContain('Building Production Assets');
    
    // Restore
    if ($originalEnv !== null) {
        $_ENV['APP_ENV'] = $originalEnv;
        putenv("APP_ENV={$originalEnv}");
    } else {
        unset($_ENV['APP_ENV']);
        putenv('APP_ENV');
    }
});
