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
namespace Tests\Fixtures\Factories;

use App\Core\Database\Factory;
use Tests\Fixtures\Models\Page;

/**
 * Page Factory (Test Fixture)
 * 
 * Creates Page model instances for testing.
 */
class PageFactory extends Factory
{
    protected string $model = Page::class;

    public function definition(): array
    {
        $title = ucfirst($this->randomString(8)) . ' ' . ucfirst($this->randomString(6));
        $slug = strtolower(str_replace(' ', '-', $title));

        return [
            'title' => $title,
            'slug' => $slug,
            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' . $this->randomString(100),
            'meta_title' => $title . ' | Infinri',
            'meta_description' => 'Description for ' . $title,
            'is_published' => $this->randomBool(),
            'created_at' => $this->randomDate('-30 days', 'now'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
