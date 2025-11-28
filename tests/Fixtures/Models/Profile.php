<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;
use App\Core\Database\Relations\BelongsTo;

class Profile extends Model
{
    protected string $table = 'profiles';

    protected array $fillable = ['user_id', 'bio', 'avatar'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
