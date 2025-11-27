<?php

declare(strict_types=1);

use App\Core\Database\Seeder;

/**
 * Page Seeder
 * 
 * Seeds the pages table with initial data.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => 'Welcome to Infinri. This is your home page.',
                'meta_title' => 'Home | Infinri',
                'meta_description' => 'Welcome to Infinri - Your digital platform.',
                'is_published' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => 'Learn more about our company and mission.',
                'meta_title' => 'About Us | Infinri',
                'meta_description' => 'Learn about Infinri and our mission.',
                'is_published' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => 'Get in touch with us.',
                'meta_title' => 'Contact | Infinri',
                'meta_description' => 'Contact Infinri for more information.',
                'is_published' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => 'Our privacy policy and data handling practices.',
                'meta_title' => 'Privacy Policy | Infinri',
                'meta_description' => 'Read our privacy policy.',
                'is_published' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($pages as $page) {
            $this->table('pages')->insert($page);
        }
    }
}
