<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;
use App\Core\Database\Relations\BelongsTo;

class Comment extends Model
{
    protected string $table = 'comments';

    protected array $fillable = ['post_id', 'body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
