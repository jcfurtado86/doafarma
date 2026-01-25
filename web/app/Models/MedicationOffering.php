<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property \Carbon\Carbon $expires_at
 */
class MedicationOffering extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationOfferingFactory> */
    use HasFactory;

    protected $table = 'medication_offerings';

    protected $fillable = [
        'doctor_id',
        'drug_id',
        'lot_number',
        'expires_at',
        'quantity',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    public function casts(): array
    {
        return [
            'expires_at' => 'date:Y-m-d',
            'quantity'   => 'integer',
        ];
    }

    /**
     * Get the doctor that owns the medication offering.
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the drug that is being offered.
     *
     * @return BelongsTo<Drug, $this>
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    /**
     * Get all requests for this offering.
     *
     * @return HasMany<MedicationRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(MedicationRequest::class);
    }

    /**
     * Get the active (pending) request for this offering.
     *
     * @return HasOne<MedicationRequest, $this>
     */
    public function activeRequest(): HasOne
    {
        return $this->hasOne(MedicationRequest::class)->where('status', 'pending');
    }

    /**
     * Scope a query to only include available offerings.
     *
     * @param Builder<MedicationOffering> $query
     * @return Builder<MedicationOffering>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include reserved offerings.
     *
     * @param Builder<MedicationOffering> $query
     * @return Builder<MedicationOffering>
     */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope a query to only include completed offerings.
     *
     * @param Builder<MedicationOffering> $query
     * @return Builder<MedicationOffering>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
