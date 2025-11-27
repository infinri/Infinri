<?php

/**
 * OPcache Preload File
 * 
 * Generated: 2025-11-27 21:23:57
 * Files: 36
 * 
 * Configure in php.ini:
 *   opcache.preload=/home/infinri/Initrix/Projects/Infinri/preload.php
 *   opcache.preload_user=www-data
 */

if (!function_exists('opcache_compile_file')) {
    return;
}

$files = [
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Application.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Concerns/ManagesPaths.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Concerns/ManagesProviders.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Config/Config.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Container/Container.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Container/ServiceProvider.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Cache/CacheInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Config/ConfigInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Container/ContainerInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Database/ConnectionInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Database/QueryBuilderInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Events/EventDispatcherInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Http/KernelInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Http/MiddlewareInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Http/RequestInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Http/ResponseInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Indexer/IndexerInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Log/LoggerInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Queue/JobInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Queue/QueueInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Routing/RouteInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Contracts/Routing/RouterInterface.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Http/Request.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Http/Response.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Module/ModuleDefinition.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Module/ModuleLoader.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Module/ModuleRegistry.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Routing/Router.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Routing/SimpleRouter.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Support/Arr.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Support/Str.php',
    '/home/infinri/Initrix/Projects/Infinri/app/Core/Support/helpers.php',
    '/home/infinri/Initrix/Projects/Infinri/var/cache/config.php',
    '/home/infinri/Initrix/Projects/Infinri/var/cache/container.php',
    '/home/infinri/Initrix/Projects/Infinri/var/cache/events.php',
    '/home/infinri/Initrix/Projects/Infinri/var/cache/modules.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        opcache_compile_file($file);
    }
}
