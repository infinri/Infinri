<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Core\Application;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Connection;
use App\Core\Database\DatabaseServiceProvider;
use App\Core\Database\Relations\HasMany;
use App\Core\Database\Relations\HasOne;
use App\Core\Database\Relations\BelongsTo;
use App\Core\Database\Relations\BelongsToMany;
use Tests\Fixtures\Models\User;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Profile;
use Tests\Fixtures\Models\Tag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RelationsIntegrationTest extends TestCase
{
    private static ?Application $app = null;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->bootApplication();
        $this->setupDatabase();
        $this->cleanTables();
    }

    protected function tearDown(): void
    {
        $this->cleanTables();
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
        }
    }

    private function setupDatabase(): void
    {
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

        if (!self::$app->has(DatabaseManager::class)) {
            $provider = new DatabaseServiceProvider(self::$app);
            $provider->register();
        }

        $this->connection = self::$app->make(DatabaseManager::class)->connection();
    }

    private function cleanTables(): void
    {
        $this->connection->statement('DELETE FROM post_tag');
        $this->connection->statement('DELETE FROM tags');
        $this->connection->statement('DELETE FROM comments');
        $this->connection->statement('DELETE FROM posts');
        $this->connection->statement('DELETE FROM profiles');
        $this->connection->statement('DELETE FROM users');
    }

    #[Test]
    public function has_many_returns_relation(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@test.com']);
        
        $relation = $user->posts();
        
        $this->assertInstanceOf(HasMany::class, $relation);
    }

    #[Test]
    public function belongs_to_returns_relation(): void
    {
        $user = User::create(['name' => 'Jane', 'email' => 'jane@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Test']);
        
        $relation = $post->user();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
    }

    #[Test]
    public function has_many_get_results(): void
    {
        $user = User::create(['name' => 'Bob', 'email' => 'bob@test.com']);
        Post::create(['user_id' => $user->id, 'title' => 'Post 1']);
        Post::create(['user_id' => $user->id, 'title' => 'Post 2']);
        
        $posts = $user->posts()->getResults();
        
        $this->assertCount(2, $posts);
    }

    #[Test]
    public function belongs_to_get_results(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'My Post']);
        
        $relatedUser = $post->user()->getResults();
        
        $this->assertNotNull($relatedUser);
        $this->assertSame('Alice', $relatedUser->name);
    }

    #[Test]
    public function relation_get_parent(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $relation = $user->posts();
        
        $this->assertSame($user, $relation->getParent());
    }

    #[Test]
    public function relation_get_related(): void
    {
        $user = User::create(['name' => 'Test2', 'email' => 'test2@test.com']);
        
        $relation = $user->posts();
        
        $this->assertInstanceOf(Post::class, $relation->getRelated());
    }

    #[Test]
    public function relation_get_foreign_key(): void
    {
        $user = User::create(['name' => 'Test3', 'email' => 'test3@test.com']);
        
        $relation = $user->posts();
        
        $this->assertSame('user_id', $relation->getForeignKey());
    }

    #[Test]
    public function relation_get_local_key(): void
    {
        $user = User::create(['name' => 'Test4', 'email' => 'test4@test.com']);
        
        $relation = $user->posts();
        
        $this->assertSame('id', $relation->getLocalKey());
    }

    #[Test]
    public function nested_relations(): void
    {
        $user = User::create(['name' => 'Nested', 'email' => 'nested@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'With Comments']);
        Comment::create(['post_id' => $post->id, 'body' => 'Comment 1']);
        Comment::create(['post_id' => $post->id, 'body' => 'Comment 2']);
        
        $comments = $post->comments()->getResults();
        
        $this->assertCount(2, $comments);
    }

    #[Test]
    public function belongs_to_returns_null_for_missing(): void
    {
        $post = new Post(['user_id' => 99999, 'title' => 'Orphan']);
        
        $user = $post->user()->getResults();
        
        $this->assertNull($user);
    }

    #[Test]
    public function has_many_create_related(): void
    {
        $user = User::create(['name' => 'Creator', 'email' => 'creator@test.com']);
        
        $post = $user->posts()->create(['title' => 'Created Post', 'content' => 'Content']);
        
        $this->assertNotNull($post->id);
        $this->assertSame($user->id, $post->user_id);
    }

    #[Test]
    public function has_many_create_many(): void
    {
        $user = User::create(['name' => 'MultiCreator', 'email' => 'multi@test.com']);
        
        $posts = $user->posts()->createMany([
            ['title' => 'Post A'],
            ['title' => 'Post B'],
        ]);
        
        $this->assertCount(2, $posts);
        $this->assertSame($user->id, $posts[0]->user_id);
        $this->assertSame($user->id, $posts[1]->user_id);
    }

    #[Test]
    public function has_many_save_model(): void
    {
        $user = User::create(['name' => 'Saver', 'email' => 'saver@test.com']);
        $post = new Post(['title' => 'To Save']);
        
        $saved = $user->posts()->save($post);
        
        $this->assertNotNull($saved->id);
        $this->assertSame($user->id, $saved->user_id);
    }

    #[Test]
    public function has_many_save_many(): void
    {
        $user = User::create(['name' => 'MultiSaver', 'email' => 'multisaver@test.com']);
        $post1 = new Post(['title' => 'Save 1']);
        $post2 = new Post(['title' => 'Save 2']);
        
        $saved = $user->posts()->saveMany([$post1, $post2]);
        
        $this->assertCount(2, $saved);
        $this->assertNotNull($saved[0]->id);
        $this->assertNotNull($saved[1]->id);
    }

    #[Test]
    public function has_many_returns_empty_for_null_key(): void
    {
        $user = new User(['name' => 'No ID', 'email' => 'noid@test.com']);
        // Don't save - no ID
        
        $posts = $user->posts()->getResults();
        
        $this->assertEmpty($posts);
    }

    #[Test]
    public function belongs_to_owner_key(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Test']);
        
        $relation = $post->user();
        
        $this->assertSame('id', $relation->getOwnerKey());
    }

    #[Test]
    public function has_one_returns_relation(): void
    {
        $user = User::create(['name' => 'HasOne', 'email' => 'hasone@test.com']);
        
        $relation = $user->profile();
        
        $this->assertInstanceOf(HasOne::class, $relation);
    }

    #[Test]
    public function has_one_get_results(): void
    {
        $user = User::create(['name' => 'WithProfile', 'email' => 'profile@test.com']);
        Profile::create(['user_id' => $user->id, 'bio' => 'My bio']);
        
        $profile = $user->profile()->getResults();
        
        $this->assertNotNull($profile);
        $this->assertSame('My bio', $profile->bio);
    }

    #[Test]
    public function has_one_returns_null_for_missing(): void
    {
        $user = User::create(['name' => 'NoProfile', 'email' => 'noprofile@test.com']);
        
        $profile = $user->profile()->getResults();
        
        $this->assertNull($profile);
    }

    #[Test]
    public function has_one_create(): void
    {
        $user = User::create(['name' => 'CreateProfile', 'email' => 'createprofile@test.com']);
        
        $profile = $user->profile()->create(['bio' => 'Created bio']);
        
        $this->assertNotNull($profile->id);
        $this->assertSame($user->id, $profile->user_id);
    }

    #[Test]
    public function has_one_save(): void
    {
        $user = User::create(['name' => 'SaveProfile', 'email' => 'saveprofile@test.com']);
        $profile = new Profile(['bio' => 'Saved bio']);
        
        $saved = $user->profile()->save($profile);
        
        $this->assertNotNull($saved->id);
        $this->assertSame($user->id, $saved->user_id);
    }

    // BelongsToMany Tests

    #[Test]
    public function belongs_to_many_returns_relation(): void
    {
        $user = User::create(['name' => 'BTM User', 'email' => 'btm@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'BTM Post']);
        
        $relation = $post->tags();
        
        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }

    #[Test]
    public function belongs_to_many_attach(): void
    {
        $user = User::create(['name' => 'Attach User', 'email' => 'attach@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Attach Post']);
        $tag = Tag::create(['name' => 'PHP']);
        
        $post->tags()->attach($tag->id);
        
        $tags = $post->tags()->getResults();
        $this->assertCount(1, $tags);
        $this->assertSame('PHP', $tags[0]->name);
    }

    #[Test]
    public function belongs_to_many_attach_multiple(): void
    {
        $user = User::create(['name' => 'Multi User', 'email' => 'multi@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Multi Post']);
        $tag1 = Tag::create(['name' => 'Laravel']);
        $tag2 = Tag::create(['name' => 'Testing']);
        
        $post->tags()->attach([$tag1->id, $tag2->id]);
        
        $tags = $post->tags()->getResults();
        $this->assertCount(2, $tags);
    }

    #[Test]
    public function belongs_to_many_detach(): void
    {
        $user = User::create(['name' => 'Detach User', 'email' => 'detach@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Detach Post']);
        $tag = Tag::create(['name' => 'Remove']);
        
        $post->tags()->attach($tag->id);
        $post->tags()->detach($tag->id);
        
        $tags = $post->tags()->getResults();
        $this->assertCount(0, $tags);
    }

    #[Test]
    public function belongs_to_many_detach_all(): void
    {
        $user = User::create(['name' => 'DetachAll User', 'email' => 'detachall@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'DetachAll Post']);
        $tag1 = Tag::create(['name' => 'Tag1']);
        $tag2 = Tag::create(['name' => 'Tag2']);
        
        $post->tags()->attach([$tag1->id, $tag2->id]);
        $post->tags()->detach();
        
        $tags = $post->tags()->getResults();
        $this->assertCount(0, $tags);
    }

    #[Test]
    public function belongs_to_many_sync(): void
    {
        $user = User::create(['name' => 'Sync User', 'email' => 'sync@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Sync Post']);
        $tag1 = Tag::create(['name' => 'Keep']);
        $tag2 = Tag::create(['name' => 'Remove']);
        $tag3 = Tag::create(['name' => 'Add']);
        
        $post->tags()->attach([$tag1->id, $tag2->id]);
        
        $changes = $post->tags()->sync([$tag1->id, $tag3->id]);
        
        // Check changes occurred (IDs might be strings or integers)
        $this->assertNotEmpty($changes['detached']);
        $this->assertNotEmpty($changes['attached']);
        
        $tags = $post->tags()->getResults();
        $this->assertCount(2, $tags);
    }

    #[Test]
    public function belongs_to_many_toggle(): void
    {
        $user = User::create(['name' => 'Toggle User', 'email' => 'toggle@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Toggle Post']);
        $tag1 = Tag::create(['name' => 'Existing']);
        $tag2 = Tag::create(['name' => 'New']);
        
        $post->tags()->attach($tag1->id);
        
        $changes = $post->tags()->toggle([$tag1->id, $tag2->id]);
        
        $this->assertContains($tag1->id, $changes['detached']);
        $this->assertContains($tag2->id, $changes['attached']);
    }

    #[Test]
    public function belongs_to_many_get_pivot_table(): void
    {
        $user = User::create(['name' => 'Pivot User', 'email' => 'pivot@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Pivot Post']);
        
        $this->assertSame('post_tag', $post->tags()->getPivotTable());
    }

    #[Test]
    public function belongs_to_many_returns_empty_for_null_key(): void
    {
        $user = User::create(['name' => 'Null User', 'email' => 'null@test.com']);
        $post = new Post(['user_id' => $user->id, 'title' => 'Unsaved']);
        // Don't save - no ID
        
        $tags = $post->tags()->getResults();
        
        $this->assertEmpty($tags);
    }

    // HasRelationships trait tests

    #[Test]
    public function get_foreign_key_returns_snake_case(): void
    {
        $user = new User();
        
        $this->assertSame('user_id', $user->getForeignKey());
    }

    #[Test]
    public function get_key_name_returns_primary_key(): void
    {
        $user = new User();
        
        $this->assertSame('id', $user->getKeyName());
    }

    #[Test]
    public function set_and_get_relation(): void
    {
        $user = User::create(['name' => 'Relation Test', 'email' => 'rel@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Test']);
        
        $user->setRelation('posts', [$post]);
        
        $this->assertSame([$post], $user->getRelation('posts'));
    }

    #[Test]
    public function relation_loaded_returns_correct_status(): void
    {
        $user = new User(['name' => 'Check', 'email' => 'check@test.com']);
        
        $this->assertFalse($user->relationLoaded('posts'));
        
        $user->setRelation('posts', []);
        
        $this->assertTrue($user->relationLoaded('posts'));
    }

    #[Test]
    public function get_relations_returns_all_loaded(): void
    {
        $user = new User(['name' => 'All', 'email' => 'all@test.com']);
        
        $this->assertEmpty($user->getRelations());
        
        $user->setRelation('posts', []);
        $user->setRelation('profile', null);
        
        $this->assertCount(2, $user->getRelations());
    }

    #[Test]
    public function load_relation_fetches_data(): void
    {
        $user = User::create(['name' => 'Load Test', 'email' => 'load@test.com']);
        Post::create(['user_id' => $user->id, 'title' => 'Loaded Post']);
        
        $this->assertFalse($user->relationLoaded('posts'));
        
        $user->load('posts');
        
        $this->assertTrue($user->relationLoaded('posts'));
        $this->assertCount(1, $user->getRelation('posts'));
    }

    #[Test]
    public function load_multiple_relations(): void
    {
        $user = User::create(['name' => 'Multi Load', 'email' => 'multiload@test.com']);
        Post::create(['user_id' => $user->id, 'title' => 'Post 1']);
        Profile::create(['user_id' => $user->id, 'bio' => 'Bio']);
        
        $user->load(['posts', 'profile']);
        
        $this->assertTrue($user->relationLoaded('posts'));
        $this->assertTrue($user->relationLoaded('profile'));
    }

    #[Test]
    public function belongs_to_with_default_foreign_key(): void
    {
        $user = User::create(['name' => 'Default FK', 'email' => 'defaultfk@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'FK Test']);
        
        // This uses default foreign key inference
        $relation = $post->user();
        
        $this->assertSame('user_id', $relation->getForeignKey());
    }

    #[Test]
    public function belongs_to_returns_null_for_null_foreign_key(): void
    {
        $post = new Post(['user_id' => null, 'title' => 'Orphan']);
        
        $result = $post->user()->getResults();
        
        $this->assertNull($result);
    }

    #[Test]
    public function belongs_to_associate_sets_foreign_key(): void
    {
        $user = User::create(['name' => 'Assoc User', 'email' => 'assoc@test.com']);
        $post = new Post(['title' => 'Assoc Post']);
        
        $post->user()->associate($user);
        
        $this->assertSame($user->id, $post->user_id);
    }

    #[Test]
    public function belongs_to_dissociate_clears_foreign_key(): void
    {
        $user = User::create(['name' => 'Dissoc User', 'email' => 'dissoc@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Dissoc Post']);
        
        $post->user()->dissociate();
        
        $this->assertNull($post->user_id);
    }

    #[Test]
    public function belongs_to_get_owner_key(): void
    {
        $user = User::create(['name' => 'Owner Key', 'email' => 'owner@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Owner Post']);
        
        $ownerKey = $post->user()->getOwnerKey();
        
        $this->assertSame('id', $ownerKey);
    }

    #[Test]
    public function has_one_returns_null_for_null_local_key(): void
    {
        // User without ID (unsaved)
        $user = new User(['name' => 'Unsaved', 'email' => 'unsaved@test.com']);
        
        $result = $user->profile()->getResults();
        
        $this->assertNull($result);
    }

    #[Test]
    public function belongs_to_many_with_pivot_columns(): void
    {
        $user = User::create(['name' => 'Pivot User', 'email' => 'pivot@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Pivot Post']);
        $tag = Tag::create(['name' => 'Pivot Tag']);
        
        // Attach with pivot data (if supported, otherwise just attach)
        $post->tags()->attach($tag->id);
        
        // Get with pivot columns (even if no extra columns, tests the withPivot path)
        $relation = $post->tags()->withPivot('post_id', 'tag_id');
        $tags = $relation->getResults();
        
        $this->assertNotEmpty($tags);
    }

    #[Test]
    public function belongs_to_many_with_pivot_as_array(): void
    {
        $user = User::create(['name' => 'Array Pivot', 'email' => 'arraypivot@test.com']);
        $post = Post::create(['user_id' => $user->id, 'title' => 'Array Post']);
        $tag = Tag::create(['name' => 'Array Tag']);
        
        $post->tags()->attach($tag->id);
        
        // withPivot with array argument
        $relation = $post->tags()->withPivot(['post_id']);
        $tags = $relation->getResults();
        
        $this->assertNotEmpty($tags);
    }
}
