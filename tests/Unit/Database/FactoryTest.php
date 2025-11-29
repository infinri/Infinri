<?php

declare(strict_types=1);


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

use App\Core\Database\Factory;
use App\Core\Database\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    #[Test]
    public function new_creates_factory_instance(): void
    {
        $factory = TestFactory::new();
        
        $this->assertInstanceOf(TestFactory::class, $factory);
    }

    #[Test]
    public function count_sets_number_to_create(): void
    {
        $factory = TestFactory::new()->count(5);
        
        $this->assertInstanceOf(TestFactory::class, $factory);
    }

    #[Test]
    public function count_returns_clone(): void
    {
        $factory1 = TestFactory::new();
        $factory2 = $factory1->count(5);
        
        $this->assertNotSame($factory1, $factory2);
    }

    #[Test]
    public function state_adds_state_modification(): void
    {
        $factory = TestFactory::new()->state(['status' => 'active']);
        
        $this->assertInstanceOf(TestFactory::class, $factory);
    }

    #[Test]
    public function state_returns_clone(): void
    {
        $factory1 = TestFactory::new();
        $factory2 = $factory1->state(['status' => 'active']);
        
        $this->assertNotSame($factory1, $factory2);
    }

    #[Test]
    public function make_creates_single_model(): void
    {
        $factory = TestFactory::new();
        $model = $factory->make();
        
        $this->assertInstanceOf(FactoryTestModel::class, $model);
    }

    #[Test]
    public function make_uses_definition(): void
    {
        $factory = TestFactory::new();
        $model = $factory->make();
        
        $this->assertSame('Test Name', $model->getAttribute('name'));
    }

    #[Test]
    public function make_applies_attributes(): void
    {
        $factory = TestFactory::new();
        $model = $factory->make(['name' => 'Custom']);
        
        $this->assertSame('Custom', $model->getAttribute('name'));
    }

    #[Test]
    public function make_applies_state(): void
    {
        $factory = TestFactory::new()->state(['status' => 'active']);
        $model = $factory->make();
        
        $this->assertSame('active', $model->getAttribute('status'));
    }

    #[Test]
    public function make_applies_callable_state(): void
    {
        $factory = TestFactory::new()->state(fn($attrs) => ['name' => $attrs['name'] . ' Modified']);
        $model = $factory->make();
        
        $this->assertSame('Test Name Modified', $model->getAttribute('name'));
    }

    #[Test]
    public function make_creates_multiple_with_count(): void
    {
        $factory = TestFactory::new()->count(3);
        $models = $factory->make();
        
        $this->assertCount(3, $models);
        $this->assertInstanceOf(FactoryTestModel::class, $models[0]);
    }

    #[Test]
    public function definition_provides_random_helpers(): void
    {
        $factory = HelperTestFactory::new();
        $model = $factory->make();
        
        $this->assertIsString($model->getAttribute('random_string'));
        $this->assertStringContainsString('@example.com', $model->getAttribute('email'));
        $this->assertIsInt($model->getAttribute('number'));
        $this->assertIsBool($model->getAttribute('active'));
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $model->getAttribute('date'));
    }
}

class FactoryTestModel extends Model
{
    protected array $fillable = ['name', 'status', 'random_string', 'email', 'number', 'active', 'date'];
}

class TestFactory extends Factory
{
    protected string $model = FactoryTestModel::class;

    public function definition(): array
    {
        return [
            'name' => 'Test Name',
            'status' => 'pending',
        ];
    }
}

class HelperTestFactory extends Factory
{
    protected string $model = FactoryTestModel::class;

    public function definition(): array
    {
        return [
            'random_string' => $this->randomString(10),
            'email' => $this->randomEmail(),
            'number' => $this->randomNumber(1, 100),
            'active' => $this->randomBool(),
            'date' => $this->randomDate('-1 week', 'now'),
        ];
    }
}
