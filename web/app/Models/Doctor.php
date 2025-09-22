<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'crm',
        'crm_uf',
    ];

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
}
