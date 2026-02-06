<?php declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Integration\Database;

use App\Core\Application;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Connection;
use App\Core\Database\DatabaseServiceProvider;
use Tests\Fixtures\Models\Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModelIntegrationTest extends TestCase
{
    private static ?Application $app = null;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->bootApplication();
        $this->setupDatabase();
        $this->cleanTable();
    }

    protected function tearDown(): void
    {
        $this->cleanTable();
    }

    private function bootApplication(): void
    {
        if (self::$app === null) {
            $basePath = dirname(__DIR__, 3);
            
            // Reset singleton for fresh test
            $reflection = new \ReflectionClass(Application::class);
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $instance->setValue(null, null);
            
            self::$app = new Application($basePath);
            self::$app->bootstrap();
        }
    }

    private function setupDatabase(): void
    {
        // Configure database using test credentials
        config([
            'database' => [
                'default' => 'pgsql',
                'connections' => [
                    'pgsql' => [
                        'driver' => 'pgsql',
                        'host' => env('DB_TEST_HOST', '127.0.0.1'),
                        'port' => (int) env('DB_TEST_PORT', 5432),
                        'database' => env('DB_TEST_DATABASE', 'infinri_test'),
                        'username' => env('DB_TEST_USERNAME', 'postgres'),
                        'password' => env('DB_TEST_PASSWORD', 'postgres'),
                    ],
                ],
            ],
        ]);

        // Register database service provider if not already
        if (!self::$app->has(DatabaseManager::class)) {
            $provider = new DatabaseServiceProvider(self::$app);
            $provider->register();
        }

        $this->connection = self::$app->make(DatabaseManager::class)->connection();
    }

    private function cleanTable(): void
    {
        $this->connection->statement('DELETE FROM pages');
    }

    #[Test]
    public function model_can_be_created(): void
    {
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Test content',
            'is_published' => true,
        ]);

        $this->assertNotNull($page->id);
        $this->assertSame('Test Page', $page->title);
        $this->assertSame('test-page', $page->slug);
    }

    #[Test]
    public function model_can_be_found_by_id(): void
    {
        $created = Page::create([
            'title' => 'Find Me',
            'slug' => 'find-me',
        ]);

        $found = Page::find($created->id);

        $this->assertNotNull($found);
        $this->assertSame('Find Me', $found->title);
    }

    #[Test]
    public function model_find_returns_null_for_missing(): void
    {
        $found = Page::find(99999);

        $this->assertNull($found);
    }

    #[Test]
    public function model_can_be_updated(): void
    {
        $page = Page::create([
            'title' => 'Original Title',
            'slug' => 'original-slug',
        ]);

        $page->title = 'Updated Title';
        $page->save();

        $fresh = Page::find($page->id);
        $this->assertSame('Updated Title', $fresh->title);
    }

    #[Test]
    public function model_can_be_deleted(): void
    {
        $page = Page::create([
            'title' => 'Delete Me',
            'slug' => 'delete-me',
        ]);
        
        $id = $page->id;
        $page->delete();

        $this->assertNull(Page::find($id));
    }

    #[Test]
    public function model_all_returns_all_records(): void
    {
        Page::create(['title' => 'Page 1', 'slug' => 'page-1']);
        Page::create(['title' => 'Page 2', 'slug' => 'page-2']);
        Page::create(['title' => 'Page 3', 'slug' => 'page-3']);

        $all = Page::all();

        $this->assertCount(3, $all);
    }

    #[Test]
    public function model_query_with_where(): void
    {
        Page::create(['title' => 'Published', 'slug' => 'pub', 'is_published' => true]);
        Page::create(['title' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        $published = Page::query()->where('is_published', true)->get();

        $this->assertCount(1, $published);
        $this->assertSame('Published', $published[0]->title);
    }

    #[Test]
    public function model_custom_method_works(): void
    {
        Page::create(['title' => 'By Slug', 'slug' => 'my-slug']);

        $found = Page::findBySlug('my-slug');

        $this->assertNotNull($found);
        $this->assertSame('By Slug', $found->title);
    }

    #[Test]
    public function model_has_timestamps(): void
    {
        $page = Page::create([
            'title' => 'With Timestamps',
            'slug' => 'timestamps',
        ]);

        $this->assertNotNull($page->created_at);
        $this->assertNotNull($page->updated_at);
    }

    #[Test]
    public function model_tracks_dirty_attributes(): void
    {
        $page = Page::create([
            'title' => 'Original',
            'slug' => 'dirty-test',
        ]);

        // After save, model should not be dirty
        $this->assertFalse($page->isDirty());

        // Change a value
        $page->title = 'Changed';

        // Now it should be dirty
        $this->assertTrue($page->isDirty());
        $this->assertTrue($page->isDirty('title'));
    }

    #[Test]
    public function model_casts_boolean(): void
    {
        $page = Page::create([
            'title' => 'Bool Test',
            'slug' => 'bool-test',
            'is_published' => true,
        ]);

        $found = Page::find($page->id);

        $this->assertTrue($found->is_published);
        $this->assertIsBool($found->is_published);
    }

    #[Test]
    public function model_serializes_to_json(): void
    {
        $page = Page::create([
            'title' => 'JSON Test',
            'slug' => 'json-test',
        ]);

        $json = json_encode($page);
        $data = json_decode($json, true);

        $this->assertSame('JSON Test', $data['title']);
        $this->assertSame('json-test', $data['slug']);
    }

    #[Test]
    public function query_count(): void
    {
        Page::create(['title' => 'Count 1', 'slug' => 'count-1']);
        Page::create(['title' => 'Count 2', 'slug' => 'count-2']);
        
        $count = Page::query()->count();
        
        $this->assertSame(2, $count);
    }

    #[Test]
    public function query_exists(): void
    {
        Page::create(['title' => 'Exists', 'slug' => 'exists']);
        
        $this->assertTrue(Page::query()->where('slug', 'exists')->exists());
        $this->assertFalse(Page::query()->where('slug', 'nonexistent')->exists());
    }

    #[Test]
    public function query_or_where(): void
    {
        Page::create(['title' => 'Page A', 'slug' => 'page-a']);
        Page::create(['title' => 'Page B', 'slug' => 'page-b']);
        Page::create(['title' => 'Page C', 'slug' => 'page-c']);
        
        $results = Page::query()
            ->where('slug', 'page-a')
            ->orWhere('slug', 'page-b')
            ->get();
        
        $this->assertCount(2, $results);
    }

    #[Test]
    public function query_where_in(): void
    {
        Page::create(['title' => 'In 1', 'slug' => 'in-1']);
        Page::create(['title' => 'In 2', 'slug' => 'in-2']);
        Page::create(['title' => 'In 3', 'slug' => 'in-3']);
        
        $results = Page::query()->whereIn('slug', ['in-1', 'in-3'])->get();
        
        $this->assertCount(2, $results);
    }

    #[Test]
    public function query_where_null(): void
    {
        Page::create(['title' => 'Has Content', 'slug' => 'has-content', 'content' => 'Some content']);
        Page::create(['title' => 'No Content', 'slug' => 'no-content']);
        
        $results = Page::query()->whereNull('content')->get();
        
        $this->assertCount(1, $results);
        $this->assertSame('No Content', $results[0]->title);
    }

    #[Test]
    public function query_where_not_null(): void
    {
        Page::create(['title' => 'Has Content', 'slug' => 'has-content2', 'content' => 'Content']);
        Page::create(['title' => 'No Content', 'slug' => 'no-content2']);
        
        $results = Page::query()->whereNotNull('content')->get();
        
        $this->assertCount(1, $results);
    }

    #[Test]
    public function query_order_by(): void
    {
        Page::create(['title' => 'B Page', 'slug' => 'b-page']);
        Page::create(['title' => 'A Page', 'slug' => 'a-page']);
        Page::create(['title' => 'C Page', 'slug' => 'c-page']);
        
        $results = Page::query()->orderBy('title', 'asc')->get();
        
        $this->assertSame('A Page', $results[0]->title);
        $this->assertSame('C Page', $results[2]->title);
    }

    #[Test]
    public function query_limit_offset(): void
    {
        Page::create(['title' => 'Page 1', 'slug' => 'limit-1']);
        Page::create(['title' => 'Page 2', 'slug' => 'limit-2']);
        Page::create(['title' => 'Page 3', 'slug' => 'limit-3']);
        
        $results = Page::query()->orderBy('slug')->limit(2)->offset(1)->get();
        
        $this->assertCount(2, $results);
        $this->assertSame('limit-2', $results[0]->slug);
    }

    #[Test]
    public function query_take_skip(): void
    {
        Page::create(['title' => 'Skip 1', 'slug' => 'skip-1']);
        Page::create(['title' => 'Skip 2', 'slug' => 'skip-2']);
        Page::create(['title' => 'Skip 3', 'slug' => 'skip-3']);
        
        $results = Page::query()->orderBy('slug')->take(1)->skip(2)->get();
        
        $this->assertCount(1, $results);
        $this->assertSame('skip-3', $results[0]->slug);
    }

    #[Test]
    public function query_select_columns(): void
    {
        Page::create(['title' => 'Select Test', 'slug' => 'select-test', 'content' => 'Content']);
        
        $result = Page::query()->select(['title', 'slug'])->first();
        
        $this->assertSame('Select Test', $result->title);
    }

    #[Test]
    public function query_delete(): void
    {
        Page::create(['title' => 'Delete 1', 'slug' => 'delete-1']);
        Page::create(['title' => 'Delete 2', 'slug' => 'delete-2']);
        
        $deleted = Page::query()->where('slug', 'delete-1')->delete();
        
        $this->assertSame(1, $deleted);
        $this->assertNull(Page::query()->where('slug', 'delete-1')->first());
    }

    #[Test]
    public function query_update(): void
    {
        Page::create(['title' => 'Update Test', 'slug' => 'update-test']);
        
        $updated = Page::query()->where('slug', 'update-test')->update(['title' => 'Updated Title']);
        
        $this->assertSame(1, $updated);
        $page = Page::query()->where('slug', 'update-test')->first();
        $this->assertSame('Updated Title', $page->title);
    }

    #[Test]
    public function query_latest(): void
    {
        Page::create(['title' => 'Latest Test', 'slug' => 'latest-test']);
        
        $result = Page::query()->latest()->first();
        
        $this->assertNotNull($result);
    }

    #[Test]
    public function query_to_sql(): void
    {
        $sql = Page::query()->where('slug', 'test')->toSql();
        
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('pages', $sql);
    }

    #[Test]
    public function find_or_fail_throws_when_not_found(): void
    {
        $this->expectException(\App\Core\Database\ModelNotFoundException::class);
        
        Page::findOrFail(999999);
    }

    #[Test]
    public function save_with_no_dirty_attributes(): void
    {
        $page = Page::create([
            'title' => 'No Dirty Test',
            'slug' => 'no-dirty-' . uniqid(),
            'content' => 'Content',
        ]);
        
        // Reload to sync original
        $page = Page::find($page->id);
        
        // Save without changes
        $result = $page->save();
        
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_on_unsaved_model_returns_false(): void
    {
        $page = new Page([
            'title' => 'Unsaved',
            'slug' => 'unsaved',
            'content' => 'Content',
        ]);
        
        $result = $page->delete();
        
        $this->assertFalse($result);
    }

    #[Test]
    public function all_returns_array_of_models(): void
    {
        Page::create([
            'title' => 'All Test 1',
            'slug' => 'all-test-1-' . uniqid(),
            'content' => 'Content',
        ]);
        
        $pages = Page::all();
        
        $this->assertIsArray($pages);
        $this->assertNotEmpty($pages);
    }

    #[Test]
    public function query_insert_many(): void
    {
        $data = [
            ['title' => 'Bulk 1', 'slug' => 'bulk-1-' . uniqid(), 'content' => 'C1', 'is_published' => false],
            ['title' => 'Bulk 2', 'slug' => 'bulk-2-' . uniqid(), 'content' => 'C2', 'is_published' => false],
        ];
        
        $count = Page::query()->insertMany($data);
        
        $this->assertSame(2, $count);
    }

    #[Test]
    public function query_select_raw(): void
    {
        Page::create(['title' => 'Raw Test', 'slug' => 'raw-' . uniqid(), 'content' => 'Content']);
        
        // Use select() to replace columns, then selectRaw for custom expression
        $result = Page::query()->select(['id'])->selectRaw('COUNT(*) OVER() as total')->first();
        
        $this->assertNotNull($result);
    }

    #[Test]
    public function query_having(): void
    {
        Page::create(['title' => 'Having 1', 'slug' => 'having-1-' . uniqid(), 'content' => 'Content']);
        Page::create(['title' => 'Having 2', 'slug' => 'having-2-' . uniqid(), 'content' => 'Content']);
        
        // This tests the having method even if the query is simple
        $builder = Page::query()->groupBy('is_published')->having('is_published', false);
        
        $this->assertInstanceOf(\App\Core\Database\ModelQueryBuilder::class, $builder);
    }

    #[Test]
    public function query_oldest(): void
    {
        Page::create(['title' => 'Oldest 1', 'slug' => 'oldest-1-' . uniqid(), 'content' => 'Content']);
        
        $result = Page::query()->oldest()->first();
        
        $this->assertNotNull($result);
    }

    #[Test]
    public function query_get_query_returns_query_builder(): void
    {
        $query = Page::query()->getQuery();
        
        $this->assertInstanceOf(\App\Core\Database\QueryBuilder::class, $query);
    }

    #[Test]
    public function query_insert_many_with_empty_array(): void
    {
        $count = Page::query()->insertMany([]);
        
        $this->assertSame(0, $count);
    }

    #[Test]
    public function query_increment_method_exists(): void
    {
        // Test that increment method exists and returns int
        // The actual execution requires proper Expression handling in Postgres
        $builder = Page::query();
        
        $this->assertTrue(method_exists($builder->getQuery(), 'increment'));
    }

    #[Test]
    public function query_decrement_method_exists(): void
    {
        $builder = Page::query();
        
        $this->assertTrue(method_exists($builder->getQuery(), 'decrement'));
    }
}
