<?php

declare(strict_types=1);

/**
 * Infinri Framework - RoadRunner Worker
 *
 * This is the entry point for RoadRunner's long-running PHP process.
 * Unlike PHP-FPM, RoadRunner keeps the PHP process alive between requests,
 * resulting in significantly faster response times.
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 */

use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;
use Nyholm\Psr7\Factory\Psr17Factory;

// Bootstrap the application once
require_once __DIR__ . '/vendor/autoload.php';

// Initialize PSR-17 factories
$factory = new Psr17Factory();

// Create the RoadRunner worker
$worker = Worker::create();
$psrWorker = new PSR7Worker($worker, $factory, $factory, $factory);

// Boot the application once (stays in memory)
$app = require_once __DIR__ . '/app/Core/bootstrap.php';

// Process requests in a loop
while (true) {
    try {
        $request = $psrWorker->waitRequest();
        
        if ($request === null) {
            break; // Worker termination signal
        }
    } catch (\Throwable $e) {
        $psrWorker->respond(
            $factory->createResponse(500)
                ->withBody($factory->createStream('Internal Server Error'))
        );
        continue;
    }

    try {
        // Convert PSR-7 request to our request format and handle
        $response = $app->handlePsr7Request($request);
        $psrWorker->respond($response);
    } catch (\Throwable $e) {
        // Log the error
        error_log(sprintf(
            "[%s] %s in %s:%d\n%s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        // Return error response
        $psrWorker->respond(
            $factory->createResponse(500)
                ->withBody($factory->createStream(
                    $app->isDebug() 
                        ? $e->getMessage() 
                        : 'Internal Server Error'
                ))
        );
    } finally {
        // Reset application state between requests
        // This is crucial for long-running processes
        if (method_exists($app, 'reset')) {
            $app->reset();
        }
        
        // Clear any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
