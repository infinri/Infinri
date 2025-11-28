<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;
use App\Core\Database\Relations\BelongsToMany;

class Tag extends Model
{
    protected string $table = 'tags';

    protected array $fillable = ['name'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag', 'tag_id', 'post_id', 'id', 'id');
    }
}
