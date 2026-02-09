<?php


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests;

use App\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Application $app = null;

    protected function setUp(): void
    {
        parent::setUp();
        Application::resetInstance();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        $this->app = null;
        parent::tearDown();
    }

    /**
     * Create a fresh Application instance for testing
     */
    protected function createApplication(): Application
    {
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_NAME=Infinri\nAPP_ENV=testing\nAPP_DEBUG=true\n");
        }

        $this->app = new Application(BASE_PATH);

        return $this->app;
    }

    /**
     * Get or create the Application instance
     */
    protected function app(): Application
    {
        return $this->app ?? $this->createApplication();
    }
}
