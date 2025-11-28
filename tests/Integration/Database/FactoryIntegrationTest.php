<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Application;
use App\Core\Database\DatabaseManager;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\Factory;
use App\Core\Database\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestPageFactory extends Factory
{
    protected string $model = TestFactoryPage::class;

    public function definition(): array
    {
        return [
            'title' => 'Test Page ' . $this->randomString(5),
            'slug' => 'test-page-' . $this->randomString(8),
            'content' => 'Test content',
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}

class TestFactoryPage extends Model
{
    protected string $table = 'pages';
    protected array $fillable = ['title', 'slug', 'content', 'is_published'];
}

class FactoryIntegrationTest extends TestCase
{
    private static ?Application $app = null;

    protected function setUp(): void
    {
        $this->bootApplication();
        $connection = self::$app->make(DatabaseManager::class)->connection();
        $connection->statement("DELETE FROM pages WHERE title LIKE 'Test Page%'");
    }

    protected function tearDown(): void
    {
        $connection = self::$app->make(DatabaseManager::class)->connection();
        $connection->statement("DELETE FROM pages WHERE title LIKE 'Test Page%'");
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
    public function factory_can_make_single_model(): void
    {
        $factory = new TestPageFactory();
        $page = $factory->make();
        
        $this->assertInstanceOf(TestFactoryPage::class, $page);
        $this->assertNull($page->id);
    }

    #[Test]
    public function factory_can_make_multiple_models(): void
    {
        $factory = new TestPageFactory();
        $pages = $factory->count(3)->make();
        
        $this->assertIsArray($pages);
        $this->assertCount(3, $pages);
    }

    #[Test]
    public function factory_can_create_single_model(): void
    {
        $factory = new TestPageFactory();
        $page = $factory->create();
        
        $this->assertInstanceOf(TestFactoryPage::class, $page);
        $this->assertNotNull($page->id);
    }

    #[Test]
    public function factory_can_create_multiple_models(): void
    {
        $factory = new TestPageFactory();
        $pages = $factory->count(3)->create();
        
        $this->assertIsArray($pages);
        $this->assertCount(3, $pages);
        
        foreach ($pages as $page) {
            $this->assertNotNull($page->id);
        }
    }

    #[Test]
    public function factory_can_apply_state(): void
    {
        $factory = new TestPageFactory();
        $page = $factory->published()->make();
        
        $this->assertTrue($page->is_published);
    }

    #[Test]
    public function factory_can_override_attributes(): void
    {
        $factory = new TestPageFactory();
        $page = $factory->make(['title' => 'Custom Title']);
        
        $this->assertSame('Custom Title', $page->title);
    }

    #[Test]
    public function factory_new_returns_instance(): void
    {
        $factory = TestPageFactory::new();
        
        $this->assertInstanceOf(TestPageFactory::class, $factory);
    }

    #[Test]
    public function factory_state_can_be_callable(): void
    {
        $factory = new TestPageFactory();
        $factory = $factory->state(fn($def) => ['title' => 'Callable Title']);
        $page = $factory->make();
        
        $this->assertSame('Callable Title', $page->title);
    }
}
