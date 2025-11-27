<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database\Model;

/**
 * Page Model
 * 
 * Represents a static content page.
 * This is the first model created as part of Phase 3.
 */
class Page extends Model
{
    /**
     * The table associated with the model
     */
    protected string $table = 'pages';

    /**
     * The attributes that are mass assignable
     */
    protected array $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    /**
     * The attributes that should be cast
     */
    protected array $casts = [
        'is_published' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Published pages only
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Find a page by its slug
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::query()->where('slug', $slug)->first();
    }

    /**
     * Get the URL for this page
     */
    public function getUrlAttribute(): string
    {
        return '/' . ltrim($this->slug ?? '', '/');
    }
}
