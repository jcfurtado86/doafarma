<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationRequest extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationRequestFactory> */
    use HasFactory;

    protected $table = 'medication_requests';

    protected $fillable = [
        'receptor_id',
        'medication_offering_id',
        'status',
    ];

    /**
     * Get the receptor (user) who made the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    /**
     * Get the medication offering that was requested.
     *
     * @return BelongsTo<MedicationOffering, $this>
     */
    public function medicationOffering(): BelongsTo
    {
        return $this->belongsTo(MedicationOffering::class);
    }

    /**
     * Scope a query to only include pending requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include confirmed requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include rejected requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }
}
