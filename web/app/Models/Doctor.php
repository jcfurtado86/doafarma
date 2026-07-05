<?php

declare(strict_types = 1);

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    #[\Override]
    protected $fillable = [
        'user_id',
        'crm',
        'crm_uf',
        'crm_verified_at',
        'crm_source',
        'specialty',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'crm_verified_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the doctor.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the medication offerings for the doctor.
     *
     * @return HasMany<MedicationOffering, $this>
     */
    public function medicationOfferings(): HasMany
    {
        return $this->hasMany(MedicationOffering::class);
    }

    /**
     * Get the ratings received by the doctor.
     *
     * @return HasMany<DoctorRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(DoctorRating::class);
    }
}
