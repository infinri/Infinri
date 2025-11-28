<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;

/**
 * Page Model (Test Fixture)
 * 
 * Example model for testing the ORM layer.
 */
class Page extends Model
{
    protected string $table = 'pages';

    protected array $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    protected array $casts = [
        'is_published' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::query()->where('slug', $slug)->first();
    }

    public function getUrlAttribute(): string
    {
        return '/' . ltrim($this->slug ?? '', '/');
    }
}
