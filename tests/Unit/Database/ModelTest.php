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
namespace Tests\Unit\Database;

use App\Core\Database\Model;
use App\Core\Database\DatabaseManager;
use App\Core\Contracts\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    #[Test]
    public function it_can_fill_attributes(): void
    {
        $model = new TestModel(['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertEquals('John', $model->name);
        $this->assertEquals('john@example.com', $model->email);
    }

    #[Test]
    public function it_can_set_and_get_attributes(): void
    {
        $model = new TestModel();
        $model->name = 'Jane';
        
        $this->assertEquals('Jane', $model->name);
    }

    #[Test]
    public function it_can_check_if_attribute_isset(): void
    {
        $model = new TestModel(['name' => 'John']);
        
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistent));
    }

    #[Test]
    public function it_can_unset_attribute(): void
    {
        $model = new TestModel(['name' => 'John']);
        unset($model->name);
        
        $this->assertFalse(isset($model->name));
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $model = new TestModel(['name' => 'John', 'email' => 'john@example.com']);
        
        $array = $model->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('John', $array['name']);
    }

    #[Test]
    public function it_converts_to_json(): void
    {
        $model = new TestModel(['name' => 'John']);
        
        $json = $model->toJson();
        
        $this->assertJson($json);
        $this->assertStringContainsString('John', $json);
    }

    #[Test]
    public function it_is_json_serializable(): void
    {
        $model = new TestModel(['name' => 'John']);
        
        $json = json_encode($model);
        
        $this->assertJson($json);
        $this->assertStringContainsString('John', $json);
    }

    #[Test]
    public function it_respects_fillable_attributes(): void
    {
        $model = new StrictModel(['name' => 'John', 'secret' => 'password']);
        
        $this->assertEquals('John', $model->name);
        $this->assertNull($model->secret);
    }

    #[Test]
    public function it_hides_attributes_in_array(): void
    {
        $model = new HiddenModel(['name' => 'John', 'password' => 'secret']);
        
        $array = $model->toArray();
        
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    #[Test]
    public function it_casts_attributes(): void
    {
        $model = new CastingModel([
            'is_active' => '1',
            'count' => '42',
            'price' => '19.99',
        ]);
        
        $this->assertTrue($model->is_active);
        $this->assertSame(42, $model->count);
        $this->assertSame(19.99, $model->price);
    }

    #[Test]
    public function it_uses_accessors(): void
    {
        $model = new AccessorModel(['first_name' => 'john', 'last_name' => 'doe']);
        
        $this->assertEquals('John', $model->first_name);
    }

    #[Test]
    public function it_uses_mutators(): void
    {
        $model = new MutatorModel();
        $model->email = 'JOHN@EXAMPLE.COM';
        
        $this->assertEquals('john@example.com', $model->getAttributes()['email']);
    }

    #[Test]
    public function it_tracks_dirty_attributes(): void
    {
        $model = new TestModel(['name' => 'John']);
        $model->syncOriginal();
        
        $this->assertFalse($model->isDirty());
        
        $model->name = 'Jane';
        
        $this->assertTrue($model->isDirty());
        $this->assertEquals(['name' => 'Jane'], $model->getDirty());
    }

    #[Test]
    public function it_generates_table_name(): void
    {
        $model = new TestModel();
        
        $this->assertEquals('test_models', $model->getTable());
    }

    #[Test]
    public function it_uses_custom_table_name(): void
    {
        $model = new CustomTableModel();
        
        $this->assertEquals('custom_table', $model->getTable());
    }

    #[Test]
    public function it_can_get_primary_key(): void
    {
        $model = new TestModel(['id' => 123, 'name' => 'John']);
        
        $this->assertEquals(123, $model->getKey());
    }

    #[Test]
    public function it_casts_string_type(): void
    {
        $model = new StringCastModel(['value' => 123]);
        
        $this->assertSame('123', $model->value);
    }

    #[Test]
    public function it_casts_array_from_json_string(): void
    {
        $model = new ArrayCastModel(['data' => '{"key":"value"}']);
        
        $this->assertSame(['key' => 'value'], $model->data);
    }

    #[Test]
    public function it_casts_json_type(): void
    {
        $model = new JsonCastModel(['payload' => '{"foo":"bar"}']);
        
        $this->assertSame(['foo' => 'bar'], $model->payload);
    }

    #[Test]
    public function it_casts_datetime_type(): void
    {
        $model = new DatetimeCastModel(['created' => '2024-01-15 10:30:00']);
        
        $this->assertInstanceOf(\DateTime::class, $model->created);
    }

    #[Test]
    public function to_array_respects_visible(): void
    {
        $model = new VisibleModel(['name' => 'John', 'email' => 'john@test.com', 'secret' => 'hidden']);
        
        $array = $model->toArray();
        
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayNotHasKey('secret', $array);
    }

    #[Test]
    public function get_dirty_returns_new_attributes(): void
    {
        $model = new TestModel();
        $model->syncOriginal(); // Set empty original
        
        // Add new attribute that wasn't in original
        $model->name = 'New Name';
        
        $dirty = $model->getDirty();
        
        $this->assertArrayHasKey('name', $dirty);
        $this->assertSame('New Name', $dirty['name']);
    }

    #[Test]
    public function get_dirty_returns_changed_attributes(): void
    {
        $model = new TestModel(['name' => 'Original']);
        $model->syncOriginal();
        
        $model->name = 'Changed';
        
        $dirty = $model->getDirty();
        
        $this->assertArrayHasKey('name', $dirty);
        $this->assertSame('Changed', $dirty['name']);
    }

    #[Test]
    public function is_dirty_returns_true_for_changes(): void
    {
        $model = new TestModel(['name' => 'Original']);
        $model->syncOriginal();
        
        $this->assertFalse($model->isDirty());
        
        $model->name = 'Changed';
        
        $this->assertTrue($model->isDirty());
    }

    #[Test]
    public function refresh_returns_self_when_not_exists(): void
    {
        $model = new TestModel(['name' => 'Test']);
        // exists is false by default
        
        $result = $model->refresh();
        
        $this->assertSame($model, $result);
    }

    #[Test]
    public function json_serialize_returns_array(): void
    {
        $model = new TestModel(['name' => 'Test', 'email' => 'test@test.com']);
        
        $json = $model->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertSame('Test', $json['name']);
    }
}

// Test model classes
class TestModel extends Model
{
    protected array $guarded = [];
}

class StringCastModel extends Model
{
    protected array $guarded = [];
    protected array $casts = ['value' => 'string'];
}

class ArrayCastModel extends Model
{
    protected array $guarded = [];
    protected array $casts = ['data' => 'array'];
}

class JsonCastModel extends Model
{
    protected array $guarded = [];
    protected array $casts = ['payload' => 'json'];
}

class DatetimeCastModel extends Model
{
    protected array $guarded = [];
    protected array $casts = ['created' => 'datetime'];
}

class VisibleModel extends Model
{
    protected array $guarded = [];
    protected array $visible = ['name', 'email'];
}

class StrictModel extends Model
{
    protected array $fillable = ['name', 'email'];
}

class HiddenModel extends Model
{
    protected array $guarded = [];
    protected array $hidden = ['password'];
}

class CastingModel extends Model
{
    protected array $guarded = [];
    protected array $casts = [
        'is_active' => 'bool',
        'count' => 'int',
        'price' => 'float',
    ];
}

class AccessorModel extends Model
{
    protected array $guarded = [];

    public function getFirstNameAttribute(?string $value): string
    {
        return ucfirst($value ?? '');
    }
}

class MutatorModel extends Model
{
    protected array $guarded = [];

    public function setEmailAttribute(string $value): string
    {
        return strtolower($value);
    }
}

class CustomTableModel extends Model
{
    protected string $table = 'custom_table';
}
