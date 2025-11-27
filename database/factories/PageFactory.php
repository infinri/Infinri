<?php

declare(strict_types=1);

use App\Core\Database\Factory;
use App\Models\Page;

/**
 * Page Factory
 * 
 * Creates Page model instances for testing and seeding.
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

    /**
     * State: Published page
     */
    public function published(): static
    {
        return $this->state([
            'is_published' => true,
        ]);
    }

    /**
     * State: Draft page
     */
    public function draft(): static
    {
        return $this->state([
            'is_published' => false,
        ]);
    }
}
