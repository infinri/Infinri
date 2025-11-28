<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;
use App\Core\Database\Relations\BelongsTo;
use App\Core\Database\Relations\BelongsToMany;
use App\Core\Database\Relations\HasMany;

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = ['user_id', 'title', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id', 'id', 'id');
    }
}
