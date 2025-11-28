<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Application;
use App\Core\Database\Connection;
use App\Core\Database\DatabaseManager;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\Seeder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestSeeder extends Seeder
{
    public array $seededData = [];

    public function run(): void
    {
        $this->seededData[] = 'ran';
    }

    public function getTable(string $table)
    {
        return $this->table($table);
    }

    public function getConnection()
    {
        return $this->connection();
    }

    public function callOther(string $class): void
    {
        $this->call($class);
    }
}

class ChildSeeder extends Seeder
{
    public static bool $wasRun = false;

    public function run(): void
    {
        self::$wasRun = true;
    }
}

class SeederIntegrationTest extends TestCase
{
    private static ?Application $app = null;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->bootApplication();
        $this->connection = self::$app->make(DatabaseManager::class)->connection();
    }

    private function bootApplication(): void
    {
        if (self::$app === null) {
            $basePath = dirname(__DIR__, 3);

            $reflection = new \ReflectionClass(Application::class);
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null, null);

            self::$app = new Application($basePath);
            self::$app->bootstrap();

            if (!self::$app->has(DatabaseManager::class)) {
                $provider = new DatabaseServiceProvider(self::$app);
                $provider->register();
            }
        }
    }

    #[Test]
    public function seeder_can_run(): void
    {
        $seeder = new TestSeeder();
        $seeder->setConnection($this->connection);
        
        $seeder->run();
        
        $this->assertContains('ran', $seeder->seededData);
    }

    #[Test]
    public function seeder_can_get_connection(): void
    {
        $seeder = new TestSeeder();
        $seeder->setConnection($this->connection);
        
        $this->assertSame($this->connection, $seeder->getConnection());
    }

    #[Test]
    public function seeder_can_get_table(): void
    {
        $seeder = new TestSeeder();
        $seeder->setConnection($this->connection);
        
        $builder = $seeder->getTable('pages');
        
        $this->assertInstanceOf(\App\Core\Database\QueryBuilder::class, $builder);
    }

    #[Test]
    public function seeder_can_call_other_seeder(): void
    {
        ChildSeeder::$wasRun = false;
        
        $seeder = new TestSeeder();
        $seeder->setConnection($this->connection);
        
        $seeder->callOther(ChildSeeder::class);
        
        $this->assertTrue(ChildSeeder::$wasRun);
    }
}
