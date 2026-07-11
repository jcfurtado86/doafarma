<?php

declare(strict_types = 1);

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    #[Override]
    protected $fillable = [
        'user_id',
        'label',
        'cep',
        'uf',
        'city',
        'neighborhood',
        'street',
        'number',
        'complement',
    ];

    /**
     * Get the user that owns the address.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the address formatted as a single readable string.
     *
     * @return Attribute<string, never>
     */
    protected function formattedAddress(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->street}, {$this->number} - {$this->neighborhood}, {$this->city} - {$this->uf}",
        );
    }
}
