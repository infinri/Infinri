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
