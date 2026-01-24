<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    /** @use HasFactory<\Database\Factories\PushTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
    ];

    /**
     * Get the user that owns the push token.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
