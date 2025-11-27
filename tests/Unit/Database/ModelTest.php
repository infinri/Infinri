<?php

declare(strict_types=1);

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
}

// Test model classes
class TestModel extends Model
{
    protected array $guarded = [];
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
