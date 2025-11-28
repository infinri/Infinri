<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Core\Database\Model;
use App\Core\Database\Relations\HasMany;
use App\Core\Database\Relations\HasOne;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}
